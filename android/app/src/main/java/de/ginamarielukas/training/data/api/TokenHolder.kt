package de.ginamarielukas.training.data.api

import java.util.concurrent.atomic.AtomicReference
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class TokenHolder @Inject constructor() {
    private val ref = AtomicReference<String?>(null)

    fun get(): String? = ref.get()
    fun set(token: String?) = ref.set(token)
}
