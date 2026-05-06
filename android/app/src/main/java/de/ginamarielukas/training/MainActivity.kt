package de.ginamarielukas.training

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.layout.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
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
class MainActivity : ComponentActivity() {

    @Inject lateinit var prefs: AppPrefs
    @Inject lateinit var health: HealthConnectManager

    private var onPermissionResult: ((Boolean) -> Unit)? = null

    private val permissionLauncher = registerForActivityResult(
        ActivityResultContracts.RequestMultiplePermissions()
    ) { grants ->
        val allGranted = grants.values.all { it }
        onPermissionResult?.invoke(allGranted)
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
                    AppNavigation()
                }
            }
        }
    }

    @Composable
    private fun AppNavigation() {
        val navController = rememberNavController()
        var startRoute    by remember { mutableStateOf<String?>(null) }

        LaunchedEffect(Unit) {
            val token = prefs.tokenFlow.first()
            startRoute = if (token == null) ROUTE_LOGIN else {
                if (health.hasAllPermissions()) ROUTE_DASHBOARD else ROUTE_PERMISSIONS
            }
        }

        if (startRoute == null) {
            Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                CircularProgressIndicator()
            }
            return
        }

        NavHost(navController = navController, startDestination = startRoute!!) {
            composable(ROUTE_LOGIN) {
                LoginScreen(onLoginSuccess = {
                    lifecycleScope.launch {
                        val dest = if (health.hasAllPermissions()) ROUTE_DASHBOARD else ROUTE_PERMISSIONS
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
                    navController.navigate(ROUTE_LOGIN) {
                        popUpTo(ROUTE_DASHBOARD) { inclusive = true }
                    }
                })
            }
        }
    }

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
                        text      = "Bitte erlaube den Zugriff in den Einstellungen, damit die App deine Gesundheitsdaten lesen kann.",
                        fontSize  = 13.sp,
                        textAlign = TextAlign.Center,
                        color     = MaterialTheme.colorScheme.error,
                    )
                }
                Button(
                    onClick  = {
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
