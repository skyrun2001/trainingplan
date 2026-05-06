package de.ginamarielukas.training.ui

import android.content.Context
import androidx.annotation.StringRes

sealed class UiText {
    data class StringResource(@StringRes val id: Int, val args: List<Any> = emptyList()) : UiText()
    data class DynamicString(val value: String) : UiText()

    fun asString(context: Context): String = when (this) {
        is StringResource -> if (args.isEmpty()) context.getString(id)
                             else context.getString(id, *args.toTypedArray())
        is DynamicString  -> value
    }
}
