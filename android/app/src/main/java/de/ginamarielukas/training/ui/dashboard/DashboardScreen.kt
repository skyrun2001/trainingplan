package de.ginamarielukas.training.ui.dashboard

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import de.ginamarielukas.training.R
import de.ginamarielukas.training.data.model.WorkoutResponse

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun DashboardScreen(
    onLogout: () -> Unit,
    vm: DashboardViewModel = hiltViewModel(),
) {
    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Text(
                        text       = if (vm.username.isNotEmpty()) "Hallo, ${vm.username}!" else "Training",
                        fontWeight = FontWeight.Bold,
                    )
                },
                actions = {
                    IconButton(onClick = { vm.refresh() }) {
                        Icon(Icons.Default.Refresh, contentDescription = stringResource(R.string.btn_refresh))
                    }
                    IconButton(onClick = { vm.logout(onLogout) }) {
                        Icon(Icons.Default.ExitToApp, contentDescription = stringResource(R.string.btn_logout))
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.surface,
                ),
            )
        }
    ) { padding ->
        if (vm.isLoading) {
            Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                CircularProgressIndicator()
            }
            return@Scaffold
        }

        LazyColumn(
            modifier            = Modifier.fillMaxSize().padding(padding).padding(horizontal = 16.dp),
            verticalArrangement = Arrangement.spacedBy(16.dp),
            contentPadding      = PaddingValues(vertical = 16.dp),
        ) {
            if (vm.error != null) {
                item {
                    Surface(
                        color = MaterialTheme.colorScheme.errorContainer,
                        shape = MaterialTheme.shapes.medium,
                    ) {
                        Text(
                            text     = vm.error!!,
                            modifier = Modifier.padding(12.dp),
                            color    = MaterialTheme.colorScheme.onErrorContainer,
                        )
                    }
                }
            }

            // Health metrics
            item {
                Text(
                    text       = "Heute",
                    fontSize   = 13.sp,
                    fontWeight = FontWeight.SemiBold,
                    color      = MaterialTheme.colorScheme.primary,
                    modifier   = Modifier.padding(bottom = 4.dp),
                )
                Row(
                    modifier            = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(12.dp),
                ) {
                    MetricCard(
                        modifier = Modifier.weight(1f),
                        icon     = Icons.Default.DirectionsWalk,
                        label    = stringResource(R.string.steps_label),
                        value    = "%,d".format(vm.steps),
                        color    = MaterialTheme.colorScheme.primary,
                    )
                    MetricCard(
                        modifier = Modifier.weight(1f),
                        icon     = Icons.Default.Bedtime,
                        label    = stringResource(R.string.sleep_label),
                        value    = "${vm.sleepMinutes / 60}h ${vm.sleepMinutes % 60}m",
                        color    = MaterialTheme.colorScheme.tertiary,
                    )
                }
            }

            item {
                MetricCard(
                    modifier = Modifier.fillMaxWidth(),
                    icon     = Icons.Default.FitnessCenter,
                    label    = stringResource(R.string.active_label),
                    value    = "${vm.activeMinutes} ${stringResource(R.string.minutes_short)}",
                    color    = MaterialTheme.colorScheme.secondary,
                )
            }

            if (vm.isSynced) {
                item {
                    Row(
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.spacedBy(6.dp),
                    ) {
                        Icon(
                            Icons.Default.CloudDone,
                            contentDescription = null,
                            tint     = MaterialTheme.colorScheme.tertiary,
                            modifier = Modifier.size(16.dp),
                        )
                        Text(
                            text     = stringResource(R.string.synced),
                            fontSize = 12.sp,
                            color    = MaterialTheme.colorScheme.tertiary,
                        )
                    }
                }
            }

            // Recent workouts
            if (vm.workouts.isNotEmpty()) {
                item {
                    Text(
                        text       = stringResource(R.string.workouts_label),
                        fontSize   = 13.sp,
                        fontWeight = FontWeight.SemiBold,
                        color      = MaterialTheme.colorScheme.primary,
                        modifier   = Modifier.padding(top = 8.dp, bottom = 4.dp),
                    )
                }
                items(vm.workouts) { workout ->
                    WorkoutCard(workout)
                }
            }

            item { Spacer(Modifier.height(16.dp)) }
        }
    }
}

@Composable
private fun MetricCard(
    modifier: Modifier = Modifier,
    icon: ImageVector,
    label: String,
    value: String,
    color: Color,
) {
    Card(
        modifier = modifier,
        shape    = MaterialTheme.shapes.large,
        colors   = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant),
    ) {
        Column(modifier = Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
            Icon(icon, contentDescription = null, tint = color, modifier = Modifier.size(24.dp))
            Text(text = value, fontSize = 22.sp, fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.onSurface)
            Text(text = label, fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
        }
    }
}

@Composable
private fun WorkoutCard(workout: WorkoutResponse) {
    val accentColor = runCatching {
        Color(android.graphics.Color.parseColor("#${workout.color}"))
    }.getOrElse { MaterialTheme.colorScheme.primary }

    Card(
        modifier = Modifier.fillMaxWidth(),
        shape    = MaterialTheme.shapes.large,
        colors   = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant),
    ) {
        Row(
            modifier          = Modifier.padding(16.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(12.dp),
        ) {
            Box(
                modifier = Modifier
                    .size(40.dp)
                    .background(accentColor.copy(alpha = 0.2f), RoundedCornerShape(10.dp)),
                contentAlignment = Alignment.Center,
            ) {
                Icon(Icons.Default.FitnessCenter, contentDescription = null, tint = accentColor, modifier = Modifier.size(20.dp))
            }
            Column(modifier = Modifier.weight(1f)) {
                Text(text = workout.label, fontWeight = FontWeight.SemiBold, fontSize = 15.sp)
                Text(text = workout.date, fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
            }
            if (workout.duration != null) {
                Text(
                    text     = "${workout.duration} min",
                    fontSize = 13.sp,
                    color    = MaterialTheme.colorScheme.onSurfaceVariant,
                )
            }
        }
    }
}
