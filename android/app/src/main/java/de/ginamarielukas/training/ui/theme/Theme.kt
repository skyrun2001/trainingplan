package de.ginamarielukas.training.ui.theme

import android.os.Build
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext

private val FallbackDark = darkColorScheme(
    primary          = Color(0xFF9C84F7),
    onPrimary        = Color(0xFF1A0D6B),
    primaryContainer = Color(0xFF312396),
    secondary        = Color(0xFF6DB6FF),
    tertiary         = Color(0xFF52D89A),
    background       = Color(0xFF0F0F14),
    surface          = Color(0xFF1A1A24),
    surfaceVariant   = Color(0xFF24243A),
    error            = Color(0xFFFF6B6B),
)

@Composable
fun TrainingTheme(content: @Composable () -> Unit) {
    val colorScheme = when {
        Build.VERSION.SDK_INT >= Build.VERSION_CODES.S ->
            dynamicDarkColorScheme(LocalContext.current)
        else -> FallbackDark
    }
    MaterialTheme(
        colorScheme = colorScheme,
        typography  = Typography(),
        content     = content,
    )
}
