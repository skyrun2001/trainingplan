package de.ginamarielukas.training.worker

import android.content.Context
import androidx.hilt.work.HiltWorker
import androidx.work.*
import dagger.assisted.Assisted
import dagger.assisted.AssistedInject
import de.ginamarielukas.training.data.api.ApiService
import de.ginamarielukas.training.data.health.HealthConnectManager
import de.ginamarielukas.training.data.model.HealthSyncRequest
import de.ginamarielukas.training.data.prefs.AppPrefs
import java.time.LocalDate
import java.util.concurrent.TimeUnit

@HiltWorker
class HealthSyncWorker @AssistedInject constructor(
    @Assisted context: Context,
    @Assisted params: WorkerParameters,
    private val health: HealthConnectManager,
    private val api: ApiService,
    private val prefs: AppPrefs,
) : CoroutineWorker(context, params) {

    override suspend fun doWork(): Result {
        if (prefs.getToken() == null) return Result.success()
        if (!health.hasAllPermissions()) return Result.success()

        return try {
            val steps         = health.getTodaySteps()
            val sleepMinutes  = health.getLastNightSleepMinutes()
            val activeMinutes = health.getTodayActiveMinutes()

            api.syncHealth(
                HealthSyncRequest(
                    date          = LocalDate.now().toString(),
                    steps         = steps,
                    sleepMinutes  = sleepMinutes,
                    activeMinutes = activeMinutes,
                )
            )
            Result.success()
        } catch (e: Exception) {
            Result.retry()
        }
    }

    companion object {
        fun schedule(context: Context) {
            val request = PeriodicWorkRequestBuilder<HealthSyncWorker>(6, TimeUnit.HOURS)
                .setConstraints(
                    Constraints.Builder()
                        .setRequiredNetworkType(NetworkType.CONNECTED)
                        .build()
                )
                .build()

            WorkManager.getInstance(context).enqueueUniquePeriodicWork(
                "health_sync",
                ExistingPeriodicWorkPolicy.KEEP,
                request,
            )
        }
    }
}
