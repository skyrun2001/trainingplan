package de.ginamarielukas.training.ui.dashboard

import androidx.compose.runtime.*
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import de.ginamarielukas.training.data.api.ApiService
import de.ginamarielukas.training.data.health.HealthConnectManager
import de.ginamarielukas.training.data.model.HealthSyncRequest
import de.ginamarielukas.training.data.model.WorkoutResponse
import de.ginamarielukas.training.data.prefs.AppPrefs
import kotlinx.coroutines.launch
import java.time.LocalDate
import javax.inject.Inject

@HiltViewModel
class DashboardViewModel @Inject constructor(
    private val health: HealthConnectManager,
    private val api: ApiService,
    private val prefs: AppPrefs,
) : ViewModel() {

    var steps         by mutableStateOf(0L)
    var sleepMinutes  by mutableStateOf(0L)
    var activeMinutes by mutableStateOf(0L)
    var workouts      by mutableStateOf<List<WorkoutResponse>>(emptyList())
    var username      by mutableStateOf("")
    var isLoading     by mutableStateOf(false)
    var isSynced      by mutableStateOf(false)
    var error         by mutableStateOf<String?>(null)

    init {
        viewModelScope.launch {
            prefs.usernameFlow.collect { username = it ?: "" }
        }
        refresh()
    }

    fun refresh() {
        viewModelScope.launch {
            isLoading = true
            isSynced  = false
            error     = null
            try {
                steps         = health.getTodaySteps()
                sleepMinutes  = health.getLastNightSleepMinutes()
                activeMinutes = health.getTodayActiveMinutes()

                api.syncHealth(
                    HealthSyncRequest(
                        date          = LocalDate.now().toString(),
                        steps         = steps,
                        sleepMinutes  = sleepMinutes,
                        activeMinutes = activeMinutes,
                    )
                )
                isSynced = true

                val resp = api.getWorkouts(5)
                if (resp.isSuccessful) workouts = resp.body() ?: emptyList()
            } catch (e: Exception) {
                error = "Fehler beim Laden der Daten"
            } finally {
                isLoading = false
            }
        }
    }

    fun logout(onDone: () -> Unit) {
        viewModelScope.launch {
            prefs.clear()
            onDone()
        }
    }
}
