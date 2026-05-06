package de.ginamarielukas.training.ui.login

import androidx.compose.runtime.*
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import de.ginamarielukas.training.R
import de.ginamarielukas.training.data.api.ApiService
import de.ginamarielukas.training.data.api.TokenHolder
import de.ginamarielukas.training.data.model.LoginRequest
import de.ginamarielukas.training.data.prefs.AppPrefs
import de.ginamarielukas.training.ui.UiText
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class LoginViewModel @Inject constructor(
    private val api: ApiService,
    private val prefs: AppPrefs,
    private val tokenHolder: TokenHolder,
) : ViewModel() {

    var username  by mutableStateOf("")
    var password  by mutableStateOf("")
    var isLoading by mutableStateOf(false)
    var error     by mutableStateOf<UiText?>(null)

    fun login(onSuccess: () -> Unit) {
        viewModelScope.launch {
            isLoading = true
            error     = null
            try {
                val response = api.login(LoginRequest(username.trim(), password))
                if (response.isSuccessful) {
                    val body = response.body() ?: run {
                        error = UiText.StringResource(R.string.error_invalid_credentials)
                        return@launch
                    }
                    tokenHolder.set(body.token)
                    prefs.saveLogin(body.token, body.username)
                    onSuccess()
                } else {
                    error = UiText.StringResource(R.string.error_invalid_credentials)
                }
            } catch (e: Exception) {
                error = UiText.StringResource(R.string.error_connection)
            } finally {
                isLoading = false
            }
        }
    }
}
