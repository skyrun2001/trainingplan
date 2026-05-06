package de.ginamarielukas.training.ui.login

import androidx.compose.runtime.*
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import de.ginamarielukas.training.data.api.ApiService
import de.ginamarielukas.training.data.model.LoginRequest
import de.ginamarielukas.training.data.prefs.AppPrefs
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class LoginViewModel @Inject constructor(
    private val api: ApiService,
    private val prefs: AppPrefs,
) : ViewModel() {

    var username by mutableStateOf("")
    var password by mutableStateOf("")
    var isLoading by mutableStateOf(false)
    var error by mutableStateOf<String?>(null)

    fun login(onSuccess: () -> Unit) {
        viewModelScope.launch {
            isLoading = true
            error     = null
            try {
                val response = api.login(LoginRequest(username.trim(), password))
                if (response.isSuccessful) {
                    val body = response.body()!!
                    prefs.saveLogin(body.token, body.username)
                    onSuccess()
                } else {
                    error = "Ungültige Anmeldedaten"
                }
            } catch (e: Exception) {
                error = "Verbindungsfehler – Server erreichbar?"
            } finally {
                isLoading = false
            }
        }
    }
}
