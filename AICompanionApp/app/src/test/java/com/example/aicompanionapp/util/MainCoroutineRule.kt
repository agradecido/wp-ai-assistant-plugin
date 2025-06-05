package com.example.aicompanionapp.util

import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.test.*
import org.junit.rules.TestWatcher
import org.junit.runner.Description

/**
 * A JUnit Test Rule that swaps the Main dispatcher with a TestDispatcher for coroutine testing.
 * This allows tests to control the execution of coroutines launched on Dispatchers.Main.
 *
 * Usage:
 * ```
 * @ExperimentalCoroutinesApi
 * class MyViewModelTest {
 *
 *     @get:Rule
 *     var mainCoroutineRule = MainCoroutineRule()
 *
 *     // ... tests ...
 * }
 * ```
 */
@ExperimentalCoroutinesApi
class MainCoroutineRule(
    private val testDispatcher: TestDispatcher = StandardTestDispatcher()
    // For more control, you can use UnconfinedTestDispatcher if tasks should run eagerly.
    // private val testDispatcher: TestDispatcher = UnconfinedTestDispatcher()
) : TestWatcher() {

    override fun starting(description: Description) {
        super.starting(description)
        Dispatchers.setMain(testDispatcher)
    }

    override fun finished(description: Description) {
        super.finished(description)
        Dispatchers.resetMain()
    }
}
