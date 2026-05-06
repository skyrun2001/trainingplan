package de.ginamarielukas.training.data.model

import com.google.gson.annotations.SerializedName

data class LoginRequest(
    val username: String,
    val password: String,
)

data class TokenResponse(
    val token: String,
    val username: String,
)

data class HealthSyncRequest(
    val date: String,
    val steps: Long?,
    @SerializedName("sleepMinutes")  val sleepMinutes: Long?,
    @SerializedName("activeMinutes") val activeMinutes: Long?,
)

data class HealthDataResponse(
    val date: String,
    val steps: Long?,
    @SerializedName("sleepMinutes")  val sleepMinutes: Long?,
    @SerializedName("activeMinutes") val activeMinutes: Long?,
)

data class WorkoutResponse(
    val id: Int,
    val date: String,
    val type: String,
    val label: String,
    val color: String,
    val duration: Int?,
)
