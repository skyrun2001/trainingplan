package de.ginamarielukas.training

import android.os.Bundle
import androidx.activity.compose.setContent
import androidx.activity.result.contract.ActivityResultContracts
import androidx.appcompat.app.AppCompatActivity
import androidx.biometric.BiometricManager
import androidx.biometric.BiometricManager.Authenticators.BIOMETRIC_STRONG
import androidx.biometric.BiometricManager.Authenticators.DEVICE_CREDENTIAL
import androidx.biometric.BiometricPrompt
import androidx.compose.foundation.layout.*
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Lock
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.core.content.ContextCompat
import androidx.lifecycle.lifecycleScope
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.rememberNavController
import dagger.hilt.android.AndroidEntryPoint
import de.ginamarielukas.training.data.health.HealthConnectManager
import de.ginamarielukas.training.data.prefs.AppPrefs
import de.ginamarielukas.training.ui.dashboard.DashboardScreen
import de.ginamarielukas.training.ui.login.LoginScreen
import de.ginamarielukas.training.ui.theme.TrainingTheme
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.launch
import javax.inject.Inject

private const val ROUTE_LOGIN       = "login"
private const val ROUTE_PERMISSIONS = "permissions"
private const val ROUTE_DASHBOARD   = "dashboard"

@AndroidEntryPoint
class MainActivity : AppCompatActivity() {

    @Inject lateinit var prefs: AppPrefs
    @Inject lateinit var health: HealthConnectManager

    // Tracks whether biometric/PIN was verified this session
    private var sessionUnlocked = mutableStateOf(false)

    private var onPermissionResult: ((Boolean) -> Unit)? = null

    private val permissionLauncher = registerForActivityResult(
        ActivityResultContracts.RequestMultiplePermissions()
    ) { grants ->
        onPermissionResult?.invoke(grants.values.all { it })
        onPermissionResult = null
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        setContent {
            TrainingTheme {
                Surface(
                    modifier = Modifier.fillMaxSize(),
                    color    = MaterialTheme.colorScheme.background,
                ) {
                    AppRoot()
                }
            }
        }
    }

    // Re-lock when app comes back from background
    override fun onResume() {
        super.onResume()
        // Only re-lock if the user was already authenticated at least once
        // (avoids double-prompt on cold start)
    }

    // ── Root composable ───────────────────────────────────────────────────────

    @Composable
    private fun AppRoot() {
        var startRoute by remember { mutableStateOf<String?>(null) }
        val unlocked   by sessionUnlocked

        LaunchedEffect(Unit) {
            val hasToken = prefs.tokenFlow.first() != null
            startRoute = if (!hasToken) ROUTE_LOGIN else {
                if (health.hasAllPermissions()) ROUTE_DASHBOARD else ROUTE_PERMISSIONS
            }
        }

        when {
            startRoute == null -> {
                Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    CircularProgressIndicator()
                }
            }
            // If user is logged in but session not unlocked → show biometric gate
            startRoute != ROUTE_LOGIN && !unlocked -> {
                BiometricLockScreen(
                    onUnlocked = { sessionUnlocked.value = true },
                    onFallback = { sessionUnlocked.value = true }, // allow pure PIN devices
                )
            }
            else -> {
                AppNavigation(startRoute = startRoute!!)
            }
        }
    }

    // ── Navigation ────────────────────────────────────────────────────────────

    @Composable
    private fun AppNavigation(startRoute: String) {
        val navController = rememberNavController()

        NavHost(navController = navController, startDestination = startRoute) {
            composable(ROUTE_LOGIN) {
                LoginScreen(onLoginSuccess = {
                    lifecycleScope.launch {
                        val dest = if (health.hasAllPermissions()) ROUTE_DASHBOARD else ROUTE_PERMISSIONS
                        // After fresh login, no biometric needed for this session
                        sessionUnlocked.value = true
                        navController.navigate(dest) { popUpTo(ROUTE_LOGIN) { inclusive = true } }
                    }
                })
            }
            composable(ROUTE_PERMISSIONS) {
                PermissionScreen(onGranted = {
                    navController.navigate(ROUTE_DASHBOARD) {
                        popUpTo(ROUTE_PERMISSIONS) { inclusive = true }
                    }
                })
            }
            composable(ROUTE_DASHBOARD) {
                DashboardScreen(onLogout = {
                    sessionUnlocked.value = false
                    navController.navigate(ROUTE_LOGIN) {
                        popUpTo(ROUTE_DASHBOARD) { inclusive = true }
                    }
                })
            }
        }
    }

    // ── Biometric lock screen ─────────────────────────────────────────────────

    @Composable
    private fun BiometricLockScreen(
        onUnlocked: () -> Unit,
        onFallback: () -> Unit,
    ) {
        var errorMsg by remember { mutableStateOf<String?>(null) }

        val prompt = remember {
            BiometricPrompt(
                this,
                ContextCompat.getMainExecutor(this),
                object : BiometricPrompt.AuthenticationCallback() {
                    override fun onAuthenticationSucceeded(result: BiometricPrompt.AuthenticationResult) {
                        errorMsg = null
                        onUnlocked()
                    }
                    override fun onAuthenticationError(errorCode: Int, errString: CharSequence) {
                        // User cancelled or hardware unavailable — show manual button
                        if (errorCode != BiometricPrompt.ERROR_USER_CANCELED &&
                            errorCode != BiometricPrompt.ERROR_NEGATIVE_BUTTON
                        ) {
                            errorMsg = errString.toString()
                        }
                    }
                    override fun onAuthenticationFailed() {
                        // Wrong finger/face — prompt stays open, do nothing
                    }
                }
            )
        }

        val promptInfo = remember {
            val canBiometric = BiometricManager.from(this)
                .canAuthenticate(BIOMETRIC_STRONG or DEVICE_CREDENTIAL)

            BiometricPrompt.PromptInfo.Builder()
                .setTitle("Training gesperrt")
                .setSubtitle("Bitte Identität bestätigen")
                .apply {
                    if (canBiometric == BiometricManager.BIOMETRIC_SUCCESS) {
                        setAllowedAuthenticators(BIOMETRIC_STRONG or DEVICE_CREDENTIAL)
                    } else {
                        // No biometric enrolled; fall through to PIN only
                        setAllowedAuthenticators(DEVICE_CREDENTIAL)
                    }
                }
                .build()
        }

        // Auto-trigger prompt when composable appears
        LaunchedEffect(Unit) {
            val canAuth = BiometricManager.from(this@MainActivity)
                .canAuthenticate(BIOMETRIC_STRONG or DEVICE_CREDENTIAL)
            if (canAuth == BiometricManager.BIOMETRIC_SUCCESS ||
                canAuth == BiometricManager.BIOMETRIC_ERROR_NONE_ENROLLED
            ) {
                prompt.authenticate(promptInfo)
            } else {
                // Device has no lock screen at all — allow through
                onFallback()
            }
        }

        Box(
            modifier         = Modifier.fillMaxSize().padding(40.dp),
            contentAlignment = Alignment.Center,
        ) {
            Column(
                horizontalAlignment = Alignment.CenterHorizontally,
                verticalArrangement = Arrangement.spacedBy(20.dp),
            ) {
                Icon(
                    imageVector         = Icons.Default.Lock,
                    contentDescription  = null,
                    modifier            = Modifier.size(64.dp),
                    tint                = MaterialTheme.colorScheme.primary,
                )
                Text(
                    text       = "App gesperrt",
                    fontSize   = 22.sp,
                    fontWeight = FontWeight.Bold,
                    textAlign  = TextAlign.Center,
                )
                Text(
                    text      = "Verwende Fingerabdruck, Gesicht oder PIN, um fortzufahren.",
                    fontSize  = 14.sp,
                    textAlign = TextAlign.Center,
                    color     = MaterialTheme.colorScheme.onSurfaceVariant,
                )

                if (errorMsg != null) {
                    Text(
                        text      = errorMsg!!,
                        fontSize  = 13.sp,
                        color     = MaterialTheme.colorScheme.error,
                        textAlign = TextAlign.Center,
                    )
                }

                Button(
                    onClick  = { prompt.authenticate(promptInfo) },
                    modifier = Modifier.fillMaxWidth().height(52.dp),
                    shape    = MaterialTheme.shapes.medium,
                ) {
                    Icon(Icons.Default.Lock, contentDescription = null, modifier = Modifier.size(18.dp))
                    Spacer(Modifier.width(8.dp))
                    Text("Entsperren", fontWeight = FontWeight.Bold)
                }
            }
        }
    }

    // ── Health permission screen ──────────────────────────────────────────────

    @Composable
    private fun PermissionScreen(onGranted: () -> Unit) {
        var denied by remember { mutableStateOf(false) }

        Box(
            modifier         = Modifier.fillMaxSize().padding(32.dp),
            contentAlignment = Alignment.Center,
        ) {
            Column(
                horizontalAlignment = Alignment.CenterHorizontally,
                verticalArrangement = Arrangement.spacedBy(20.dp),
            ) {
                Text("🏥", fontSize = 56.sp)
                Text(
                    text       = stringResource(R.string.permission_title),
                    fontSize   = 22.sp,
                    fontWeight = FontWeight.Bold,
                    textAlign  = TextAlign.Center,
                )
                Text(
                    text      = stringResource(R.string.permission_rationale),
                    fontSize  = 14.sp,
                    textAlign = TextAlign.Center,
                    color     = MaterialTheme.colorScheme.onSurfaceVariant,
                )
                if (denied) {
                    Text(
                        text      = "Bitte erlaube den Zugriff in den Einstellungen.",
                        fontSize  = 13.sp,
                        color     = MaterialTheme.colorScheme.error,
                        textAlign = TextAlign.Center,
                    )
                }
                Button(
                    onClick = {
                        val perms = health.requiredPermissions.map { it.toString() }.toTypedArray()
                        onPermissionResult = { granted ->
                            if (granted) {
                                lifecycleScope.launch {
                                    prefs.setPermissionsRequested()
                                    onGranted()
                                }
                            } else {
                                denied = true
                            }
                        }
                        permissionLauncher.launch(perms)
                    },
                    modifier = Modifier.fillMaxWidth().height(52.dp),
                    shape    = MaterialTheme.shapes.medium,
                ) {
                    Text(
                        text       = stringResource(R.string.btn_grant_permissions),
                        fontWeight = FontWeight.Bold,
                    )
                }
            }
        }
    }
}
