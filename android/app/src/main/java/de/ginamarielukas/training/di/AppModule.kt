package de.ginamarielukas.training.di

import dagger.Module
import dagger.Provides
import dagger.hilt.InstallIn
import dagger.hilt.components.SingletonComponent
import de.ginamarielukas.training.data.api.ApiService
import de.ginamarielukas.training.data.api.buildApiService
import de.ginamarielukas.training.data.prefs.AppPrefs
import kotlinx.coroutines.runBlocking
import javax.inject.Singleton

@Module
@InstallIn(SingletonComponent::class)
object AppModule {

    @Provides
    @Singleton
    fun provideApiService(prefs: AppPrefs): ApiService =
        buildApiService { runBlocking { prefs.getToken() } }
}
