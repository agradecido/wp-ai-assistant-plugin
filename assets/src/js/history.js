/**
 * History functionality for WP AI Assistant plugin
 */
document.addEventListener('DOMContentLoaded', () => {
    const historyData = window.wpAIAssistantHistory || {};

    /**
     * Update empty state visibility based on current threads.
     */
    function updateEmptyState() {
        const threadsContainer = document.querySelector('.wpai-history-threads');
        const emptyState = document.querySelector('.wpai-history-empty');

        if (!threadsContainer || !emptyState) {
            return;
        }

        const allThreads = threadsContainer.querySelectorAll('.wpai-thread');
        const visibleThreads = threadsContainer.querySelectorAll('.wpai-thread:not(.wpai-thread-hidden)');

        if (allThreads.length === 0) {
            emptyState.textContent = historyData?.i18n?.noConversations || '';
            emptyState.style.display = 'block';
            return;
        }

        if (visibleThreads.length === 0) {
            emptyState.textContent = historyData?.i18n?.noFilterResults || '';
            emptyState.style.display = 'block';
        } else {
            emptyState.style.display = 'none';
        }
    }

    /**
     * Set thread button label when showing/hiding messages.
     *
     * @param {HTMLElement|null} button
     * @param {boolean} isVisible
     */
    function setThreadButtonLabel(button, isVisible) {
        if (!button || !historyData.i18n) {
            return;
        }

        button.textContent = isVisible
            ? historyData.i18n.hideConversation
            : historyData.i18n.viewFullConversation;
    }

    /**
     * Show thread messages.
     *
     * @param {HTMLElement} thread
     * @param {HTMLElement|null} button
     */
    function showThreadMessages(thread, button = null) {
        if (!thread) {
            return;
        }
        const messagesContainer = thread.querySelector('.wpai-thread-messages');
        if (!messagesContainer) {
            return;
        }

        messagesContainer.style.display = 'block';
        setThreadButtonLabel(button, true);
    }

    /**
     * Hide thread messages.
     *
     * @param {HTMLElement} thread
     * @param {HTMLElement|null} button
     */
    function hideThreadMessages(thread, button = null) {
        if (!thread) {
            return;
        }
        const messagesContainer = thread.querySelector('.wpai-thread-messages');
        if (!messagesContainer) {
            return;
        }

        messagesContainer.style.display = 'none';
        setThreadButtonLabel(button, false);
    }

    /**
     * Toggle thread messages visibility.
     *
     * @param {HTMLElement} thread
     * @param {HTMLElement} button
     */
    function toggleThreadMessages(thread, button) {
        if (!thread) {
            return;
        }
        const messagesContainer = thread.querySelector('.wpai-thread-messages');
        if (!messagesContainer) {
            return;
        }

        const shouldShow = messagesContainer.style.display !== 'block';
        if (shouldShow) {
            showThreadMessages(thread, button);
        } else {
            hideThreadMessages(thread, button);
        }
    }

    /**
     * Continue chat helper shared by buttons and bulk actions.
     *
     * @param {string} threadId
     * @param {string|null} redirectUrl
     */
    function continueChat(threadId, redirectUrl = null) {
        if (!threadId) {
            return;
        }

        try {
            sessionStorage.setItem('wpai_current_thread', threadId);
        } catch (e) {
            if (historyData?.i18n?.sessionStorageNotAvailable) {
                console.log(historyData.i18n.sessionStorageNotAvailable);
            }
        }

        const chatbotContainer = document.getElementById('chatbot-container');

        if (chatbotContainer) {
            chatbotContainer.setAttribute('data-thread-id', threadId);

            const outputEl = chatbotContainer.querySelector('#chat-output');
            if (outputEl) {
                const botMessage = document.createElement('div');
                botMessage.className = 'chat-message assistant';
                botMessage.innerHTML = historyData?.i18n?.continueConversationMessage || '';
                outputEl.appendChild(botMessage);
                outputEl.style.display = 'block';
                outputEl.scrollTop = outputEl.scrollHeight;
            }

            const inputEl = chatbotContainer.querySelector('#chat-input');
            if (inputEl) {
                inputEl.setAttribute(
                    'placeholder',
                    historyData?.i18n?.continueConversationPlaceholder || ''
                );
                inputEl.focus();
            }

            chatbotContainer.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });

            return;
        }

        if (redirectUrl) {
            window.location.href = redirectUrl;
            return;
        }

        if (historyData?.i18n?.chatbotNotAvailableAlert) {
            alert(historyData.i18n.chatbotNotAvailableAlert);
        }
    }

    /**
     * Initialize view thread functionality
     */
    function initViewThreadButtons() {
        const viewButtons = document.querySelectorAll('.wpai-view-thread');
        if (viewButtons.length === 0) return;

        viewButtons.forEach(button => {
            button.addEventListener('click', function() {
                const threadContainer = this.closest('.wpai-thread');
                toggleThreadMessages(threadContainer, this);
            });
        });
    }

    /**
     * Initialize continue chat functionality
     */
    function initContinueChatButtons() {
        const continueButtons = document.querySelectorAll('.wpai-continue-chat');
        if (continueButtons.length === 0) return;

        continueButtons.forEach(button => {
            button.addEventListener('click', function() {
                const threadId = this.getAttribute('data-thread-id');
                const redirectUrl = this.getAttribute('data-redirect-url');
                if (!threadId) return;

                continueChat(threadId, redirectUrl || null);
            });
        });
    }

    /**
     * Initialize filter controls so nothing is selected by default.
     */
    function initFilters() {
        const filterInputs = document.querySelectorAll('.wpai-filter-input');
        if (filterInputs.length === 0) {
            return;
        }

        filterInputs.forEach(input => {
            input.checked = false;
        });

        function applyFilters() {
            const threads = document.querySelectorAll('.wpai-thread');
            const activeFilters = Array.from(filterInputs)
                .filter(input => input.checked)
                .map(input => input.dataset.filter);

            threads.forEach(thread => {
                const status = thread.dataset.status;
                const shouldHide = activeFilters.length > 0 && !activeFilters.includes(status);
                thread.classList.toggle('wpai-thread-hidden', shouldHide);
            });

            updateEmptyState();
            document.dispatchEvent(new CustomEvent('wpai:refresh-selection'));
        }

        filterInputs.forEach(input => {
            input.addEventListener('change', applyFilters);
        });

        applyFilters();
    }

    /**
     * Initialize bulk selection and actions.
     */
    function initSelection() {
        const actionBar = document.querySelector('.wpai-bulk-actions');
        const actionSelect = document.getElementById('wpai-bulk-action-select');
        const applyButton = document.getElementById('wpai-bulk-action-apply');
        const selectionCount = document.querySelector('.wpai-selection-count');
        const selectAll = document.getElementById('wpai-select-all');

        if (!actionBar || !actionSelect || !applyButton) {
            return;
        }

        function getAllCheckboxes() {
            return Array.from(document.querySelectorAll('.wpai-thread-checkbox'));
        }

        function getVisibleCheckboxes() {
            return getAllCheckboxes().filter(checkbox => {
                const thread = checkbox.closest('.wpai-thread');
                return thread && !thread.classList.contains('wpai-thread-hidden');
            });
        }

        function getSelectedCheckboxes() {
            return getAllCheckboxes().filter(checkbox => checkbox.checked);
        }

        function toggleActionBar(visible) {
            if (visible) {
                actionBar.classList.add('visible');
                actionBar.setAttribute('aria-hidden', 'false');
            } else {
                actionBar.classList.remove('visible');
                actionBar.setAttribute('aria-hidden', 'true');
                actionSelect.value = '';
            }
        }

        function updateSelectionCount(count) {
            if (!selectionCount) {
                return;
            }

            const template = count === 1
                ? historyData?.i18n?.selectionSingle
                : historyData?.i18n?.selectionPlural;

            if (template) {
                selectionCount.textContent = template.replace('%d', count);
            } else {
                selectionCount.textContent = `${count}`;
            }
        }

        function updateSelectionState() {
            const selected = getSelectedCheckboxes();
            const visibleCheckboxes = getVisibleCheckboxes();

            if (selectAll) {
                const allVisibleSelected = selected.length > 0 && selected.length === visibleCheckboxes.length;
                selectAll.checked = allVisibleSelected && visibleCheckboxes.length > 0;
                selectAll.indeterminate = selected.length > 0 && selected.length < visibleCheckboxes.length;
            }

            updateSelectionCount(selected.length);
            toggleActionBar(selected.length > 0);
        }

        function handleBulkView(selectedCheckboxes) {
            const firstCheckbox = selectedCheckboxes[0];
            if (!firstCheckbox) {
                return;
            }

            const thread = firstCheckbox.closest('.wpai-thread');
            if (!thread) {
                return;
            }

            const button = thread.querySelector('.wpai-view-thread');
            showThreadMessages(thread, button || null);
            thread.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function handleBulkDelete(selectedCheckboxes) {
            if (!historyData?.i18n?.deleteConfirmation) {
                return;
            }

            if (!window.confirm(historyData.i18n.deleteConfirmation)) {
                return;
            }

            const ids = selectedCheckboxes.map(checkbox => checkbox.value).filter(Boolean);
            if (ids.length === 0) {
                return;
            }

            const params = new URLSearchParams();
            params.append('action', 'wp_ai_assistant_delete_threads');
            params.append('thread_ids', JSON.stringify(ids));
            params.append('_ajax_nonce', historyData.nonce);

            fetch(historyData.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                credentials: 'same-origin',
                body: params.toString()
            })
                .then(response => response.json())
                .then(data => {
                    if (!data || data.success !== true) {
                        const message = data?.data?.message || historyData?.i18n?.deleteError;
                        throw new Error(message);
                    }

                    const deleted = Array.isArray(data.data?.deleted) ? data.data.deleted : [];
                    const failed = Array.isArray(data.data?.failed) ? data.data.failed : [];

                    deleted.forEach(postId => {
                        const thread = document.querySelector(`.wpai-thread[data-post-id="${postId}"]`);
                        if (thread) {
                            thread.remove();
                        }
                    });

                    if (failed.length > 0 && historyData?.i18n?.deletePartial) {
                        alert(historyData.i18n.deletePartial);
                    }

                    document.dispatchEvent(new CustomEvent('wpai:refresh-selection'));
                    updateEmptyState();
                })
                .catch(error => {
                    const message = error?.message || historyData?.i18n?.deleteError || '';
                    if (message) {
                        alert(message);
                    }
                });
        }

        function handleBulkAction() {
            const selectedCheckboxes = getSelectedCheckboxes();
            if (selectedCheckboxes.length === 0) {
                if (historyData?.i18n?.noSelection) {
                    alert(historyData.i18n.noSelection);
                }
                return;
            }

            const action = actionSelect.value;
            if (!action) {
                if (historyData?.i18n?.selectAction) {
                    alert(historyData.i18n.selectAction);
                }
                return;
            }

            if ('view' === action) {
                handleBulkView(selectedCheckboxes);
            } else if ('delete' === action) {
                handleBulkDelete(selectedCheckboxes);
            }
        }

        function handleSelectAllChange() {
            const isChecked = selectAll.checked;
            const visibleCheckboxes = getVisibleCheckboxes();

            visibleCheckboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
            });

            updateSelectionState();
        }

        getAllCheckboxes().forEach(checkbox => {
            checkbox.addEventListener('change', updateSelectionState);
        });

        if (selectAll) {
            selectAll.checked = false;
            selectAll.indeterminate = false;
            selectAll.addEventListener('change', handleSelectAllChange);
        }

        applyButton.addEventListener('click', handleBulkAction);

        document.addEventListener('wpai:refresh-selection', updateSelectionState);

        updateSelectionState();
    }

    initViewThreadButtons();
    initContinueChatButtons();
    initFilters();
    initSelection();
    updateEmptyState();
});
