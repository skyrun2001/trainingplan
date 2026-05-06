package de.ginamarielukas.training

import android.app.Application
import androidx.hilt.work.HiltWorkerFactory
import androidx.work.Configuration
import dagger.hilt.android.HiltAndroidApp
import de.ginamarielukas.training.worker.HealthSyncWorker
import javax.inject.Inject

@HiltAndroidApp
class TrainingApp : Application(), Configuration.Provider {

    @Inject lateinit var workerFactory: HiltWorkerFactory

    override val workManagerConfiguration: Configuration
        get() = Configuration.Builder()
            .setWorkerFactory(workerFactory)
            .build()

    override fun onCreate() {
        super.onCreate()
        HealthSyncWorker.schedule(this)
    }
}
