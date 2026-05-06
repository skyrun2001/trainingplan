package de.ginamarielukas.training.data.health

import android.content.Context
import androidx.health.connect.client.HealthConnectClient
import androidx.health.connect.client.permission.HealthPermission
import androidx.health.connect.client.records.ExerciseSessionRecord
import androidx.health.connect.client.records.SleepSessionRecord
import androidx.health.connect.client.records.StepsRecord
import androidx.health.connect.client.request.AggregateRequest
import androidx.health.connect.client.request.ReadRecordsRequest
import androidx.health.connect.client.time.TimeRangeFilter
import dagger.hilt.android.qualifiers.ApplicationContext
import java.time.Duration
import java.time.LocalDate
import java.time.ZoneId
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class HealthConnectManager @Inject constructor(
    @ApplicationContext private val context: Context,
) {
    private val client: HealthConnectClient by lazy { HealthConnectClient.getOrCreate(context) }

    val requiredPermissions = setOf(
        HealthPermission.getReadPermission(StepsRecord::class),
        HealthPermission.getReadPermission(SleepSessionRecord::class),
        HealthPermission.getReadPermission(ExerciseSessionRecord::class),
    )

    suspend fun hasAllPermissions(): Boolean =
        client.permissionController.getGrantedPermissions().containsAll(requiredPermissions)

    suspend fun getTodaySteps(): Long {
        val zone  = ZoneId.systemDefault()
        val today = LocalDate.now()
        val start = today.atStartOfDay(zone).toInstant()
        val end   = today.plusDays(1).atStartOfDay(zone).toInstant()

        val result = client.aggregate(
            AggregateRequest(
                metrics         = setOf(StepsRecord.COUNT_TOTAL),
                timeRangeFilter = TimeRangeFilter.between(start, end),
            )
        )
        return result[StepsRecord.COUNT_TOTAL] ?: 0L
    }

    suspend fun getLastNightSleepMinutes(): Long {
        val zone      = ZoneId.systemDefault()
        val yesterday = LocalDate.now().minusDays(1)
        val start     = yesterday.atTime(20, 0).atZone(zone).toInstant()
        val end       = LocalDate.now().atTime(12, 0).atZone(zone).toInstant()

        val result = client.readRecords(
            ReadRecordsRequest(
                recordType      = SleepSessionRecord::class,
                timeRangeFilter = TimeRangeFilter.between(start, end),
            )
        )
        return result.records.sumOf { Duration.between(it.startTime, it.endTime).toMinutes() }
    }

    suspend fun getTodayActiveMinutes(): Long {
        val zone  = ZoneId.systemDefault()
        val today = LocalDate.now()
        val start = today.atStartOfDay(zone).toInstant()
        val end   = today.plusDays(1).atStartOfDay(zone).toInstant()

        val result = client.readRecords(
            ReadRecordsRequest(
                recordType      = ExerciseSessionRecord::class,
                timeRangeFilter = TimeRangeFilter.between(start, end),
            )
        )
        return result.records.sumOf { Duration.between(it.startTime, it.endTime).toMinutes() }
    }
}
