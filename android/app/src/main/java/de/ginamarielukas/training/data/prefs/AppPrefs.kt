package de.ginamarielukas.training.data.prefs

import android.content.Context
import androidx.datastore.core.DataStore
import androidx.datastore.preferences.core.*
import androidx.datastore.preferences.preferencesDataStore
import dagger.hilt.android.qualifiers.ApplicationContext
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.flow.map
import javax.inject.Inject
import javax.inject.Singleton

private val Context.dataStore: DataStore<Preferences> by preferencesDataStore(name = "app_prefs")

@Singleton
class AppPrefs @Inject constructor(@ApplicationContext private val context: Context) {

    private val store = context.dataStore

    companion object {
        private val KEY_TOKEN    = stringPreferencesKey("api_token")
        private val KEY_USERNAME = stringPreferencesKey("username")
        private val KEY_PERMS    = booleanPreferencesKey("health_permissions_requested")
    }

    val tokenFlow: Flow<String?> = store.data.map { it[KEY_TOKEN] }
    val usernameFlow: Flow<String?> = store.data.map { it[KEY_USERNAME] }
    val permissionsRequestedFlow: Flow<Boolean> = store.data.map { it[KEY_PERMS] ?: false }

    suspend fun getToken(): String? = store.data.first()[KEY_TOKEN]

    suspend fun saveLogin(token: String, username: String) {
        store.edit {
            it[KEY_TOKEN]    = token
            it[KEY_USERNAME] = username
        }
    }

    suspend fun setPermissionsRequested() {
        store.edit { it[KEY_PERMS] = true }
    }

    suspend fun clear() {
        store.edit { it.clear() }
    }
}
