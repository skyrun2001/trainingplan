package de.ginamarielukas.training.ui.login

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.text.KeyboardActions
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.focus.FocusDirection
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.platform.LocalFocusManager
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.*
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import de.ginamarielukas.training.R
import de.ginamarielukas.training.ui.UiText

@Composable
fun LoginScreen(
    onLoginSuccess: () -> Unit,
    vm: LoginViewModel = hiltViewModel(),
) {
    val focusManager = LocalFocusManager.current

    Box(
        modifier        = Modifier.fillMaxSize().padding(24.dp),
        contentAlignment = Alignment.Center,
    ) {
        Card(
            modifier = Modifier.fillMaxWidth(),
            colors   = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant),
            shape    = MaterialTheme.shapes.extraLarge,
        ) {
            Column(
                modifier            = Modifier.padding(28.dp),
                verticalArrangement = Arrangement.spacedBy(16.dp),
            ) {
                Text(
                    text       = stringResource(R.string.login_title),
                    fontSize   = 22.sp,
                    fontWeight = FontWeight.Bold,
                    color      = MaterialTheme.colorScheme.onSurface,
                )
                Text(
                    text     = stringResource(R.string.login_subtitle),
                    fontSize = 13.sp,
                    color    = MaterialTheme.colorScheme.onSurfaceVariant,
                )

                if (vm.error != null) {
                    val ctx = LocalContext.current
                    Surface(
                        color  = MaterialTheme.colorScheme.errorContainer,
                        shape  = MaterialTheme.shapes.medium,
                    ) {
                        Text(
                            text     = vm.error!!.asString(ctx),
                            modifier = Modifier.padding(12.dp),
                            color    = MaterialTheme.colorScheme.onErrorContainer,
                            fontSize = 13.sp,
                        )
                    }
                }

                OutlinedTextField(
                    value         = vm.username,
                    onValueChange = { vm.username = it },
                    label         = { Text(stringResource(R.string.label_username)) },
                    singleLine    = true,
                    modifier      = Modifier.fillMaxWidth(),
                    keyboardOptions = KeyboardOptions(
                        keyboardType = KeyboardType.Text,
                        imeAction    = ImeAction.Next,
                    ),
                    keyboardActions = KeyboardActions(
                        onNext = { focusManager.moveFocus(FocusDirection.Down) }
                    ),
                )

                OutlinedTextField(
                    value         = vm.password,
                    onValueChange = { vm.password = it },
                    label         = { Text(stringResource(R.string.label_password)) },
                    singleLine    = true,
                    modifier      = Modifier.fillMaxWidth(),
                    visualTransformation = PasswordVisualTransformation(),
                    keyboardOptions = KeyboardOptions(
                        keyboardType = KeyboardType.Password,
                        imeAction    = ImeAction.Done,
                    ),
                    keyboardActions = KeyboardActions(
                        onDone = {
                            focusManager.clearFocus()
                            vm.login(onLoginSuccess)
                        }
                    ),
                )

                Button(
                    onClick  = { vm.login(onLoginSuccess) },
                    enabled  = !vm.isLoading && vm.username.isNotBlank() && vm.password.isNotBlank(),
                    modifier = Modifier.fillMaxWidth().height(50.dp),
                    shape    = MaterialTheme.shapes.medium,
                ) {
                    if (vm.isLoading) {
                        CircularProgressIndicator(
                            modifier = Modifier.size(20.dp),
                            color    = MaterialTheme.colorScheme.onPrimary,
                            strokeWidth = 2.dp,
                        )
                    } else {
                        Text(
                            text       = stringResource(R.string.btn_login),
                            fontWeight = FontWeight.Bold,
                        )
                    }
                }
            }
        }
    }
}
