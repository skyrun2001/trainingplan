package de.ginamarielukas.training.data.api

import de.ginamarielukas.training.data.model.*
import retrofit2.Response
import retrofit2.http.*

interface ApiService {

    @POST("api/auth/token")
    suspend fun login(@Body request: LoginRequest): Response<TokenResponse>

    @POST("api/health/sync")
    suspend fun syncHealth(@Body request: HealthSyncRequest): Response<Unit>

    @GET("api/health/data")
    suspend fun getHealthData(@Query("days") days: Int = 7): Response<List<HealthDataResponse>>

    @GET("api/health/workouts")
    suspend fun getWorkouts(@Query("limit") limit: Int = 5): Response<List<WorkoutResponse>>
}
