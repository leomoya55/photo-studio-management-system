'use strict';

(function() {
    document.addEventListener('DOMContentLoaded', function() {
        const wrapper = document.querySelector('[data-support-messages]');
        if (!wrapper) {
            return;
        }

        const apiUrl = wrapper.dataset.api || '';
        const role = wrapper.dataset.role || 'user';
        let selectedUserId = parseInt(wrapper.dataset.userId || '0', 10);
        let selectedUserName = wrapper.dataset.userName || '';
        let selectedUserEmail = wrapper.dataset.userEmail || '';

        const threadListEl = wrapper.querySelector('[data-message-list]');
        const form = wrapper.querySelector('form[data-support-form]');
        const messageInput = form ? form.querySelector('textarea[name="message"]') : null;
        const subjectInput = form ? form.querySelector('input[name="subject"]') : null;
        const hiddenUserIdInput = form ? form.querySelector('input[name="user_id"]') : null;
        const statusEl = wrapper.querySelector('[data-support-status]');
        const emptyState = wrapper.querySelector('[data-empty-state]');
        const loadingEl = wrapper.querySelector('[data-loading]');
        const overviewPanel = wrapper.querySelector('[data-overview-panel]');
        const overviewList = wrapper.querySelector('[data-thread-list]');
        const refreshBtn = wrapper.querySelector('[data-refresh-threads]');
        const selectedUserLabel = wrapper.querySelector('[data-selected-user]');
        const closeThreadBtn = wrapper.querySelector('[data-close-thread]');
        const adminBadge = document.querySelector('[data-admin-message-count]');

        let pollInterval = null;

        function escapeHtml(str) {
            return String(str || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function updateAdminBadge(value) {
            if (!adminBadge || role !== 'admin') {
                return;
            }
            const total = Number(value || 0);
            if (total > 0) {
                adminBadge.textContent = total;
                adminBadge.classList.remove('d-none');
            } else {
                adminBadge.textContent = '0';
                adminBadge.classList.add('d-none');
            }
        }

        function updateSelectedUserLabel() {
            if (!selectedUserLabel || role !== 'admin') {
                return;
            }
            if (selectedUserId) {
                const display = selectedUserName || ('Cliente #' + selectedUserId);
                selectedUserLabel.textContent = selectedUserEmail ? display + ' · ' + selectedUserEmail : display;
            } else {
                selectedUserLabel.textContent = 'Selecciona una conversación para ver los mensajes';
            }
        }

        function toggleLoading(state) {
            if (loadingEl) {
                loadingEl.classList.toggle('d-none', !state);
            }
        }

        function showStatus(message, level) {
            if (!statusEl) {
                return;
            }
            statusEl.textContent = message || '';
            statusEl.className = '';
            statusEl.classList.add('support-status', level ? 'support-status-' + level : 'support-status-muted');
            if (message) {
                statusEl.classList.remove('d-none');
            } else {
                statusEl.classList.add('d-none');
            }
        }

        function formatDate(isoString) {
            if (!isoString) {
                return '';
            }
            const date = new Date(isoString);
            if (Number.isNaN(date.getTime())) {
                return isoString;
            }
            const options = { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' };
            return date.toLocaleString('es-CR', options);
        }

        function renderMessages(list) {
            if (!threadListEl) {
                return;
            }
            threadListEl.innerHTML = '';
            const fragment = document.createDocumentFragment();
            (list || []).forEach(function(item) {
                const bubble = document.createElement('div');
                bubble.className = 'message-bubble ' + (item.sender === 'admin' ? 'from-admin' : 'from-user');

                const meta = document.createElement('div');
                meta.className = 'message-meta';
                meta.textContent = (item.sender === 'admin' ? 'Estudio' : 'Tú') + ' · ' + formatDate(item.created_at);
                bubble.appendChild(meta);

                if (item.subject) {
                    const subjectEl = document.createElement('div');
                    subjectEl.className = 'message-subject';
                    subjectEl.textContent = item.subject;
                    bubble.appendChild(subjectEl);
                }

                const body = document.createElement('p');
                body.className = 'message-body';
                body.textContent = item.message;
                bubble.appendChild(body);

                fragment.appendChild(bubble);
            });
            threadListEl.appendChild(fragment);

            if (emptyState) {
                emptyState.classList.toggle('d-none', (list || []).length > 0);
            }

            if (threadListEl.scrollHeight > threadListEl.clientHeight) {
                threadListEl.scrollTop = threadListEl.scrollHeight;
            }
        }

        function fetchThread(userId) {
            if (!apiUrl) {
                return;
            }

            toggleLoading(true);
            showStatus('', 'muted');

            let url = apiUrl;
            if (role === 'admin' && userId) {
                url += (apiUrl.includes('?') ? '&' : '?') + 'user_id=' + encodeURIComponent(userId);
            }

            if (role === 'admin' && selectedUserLabel) {
                const display = selectedUserName || (userId ? 'cliente #' + userId : 'la conversación');
                selectedUserLabel.textContent = 'Cargando conversación con ' + display + '…';
            }

            fetch(url, { credentials: 'same-origin' })
                .then(function(resp) {
                    if (!resp.ok) {
                        throw new Error('Error al cargar los mensajes');
                    }
                    return resp.json();
                })
                .then(function(payload) {
                    if (!payload.success) {
                        throw new Error(payload.message || 'No fue posible cargar los mensajes.');
                    }

                    renderMessages(payload.messages || []);

                    if (hiddenUserIdInput && role === 'admin') {
                        hiddenUserIdInput.value = payload.user_id || '';
                    }

                    if (role === 'admin') {
                        selectedUserId = Number(payload.user_id || userId || 0);
                        wrapper.dataset.userId = String(selectedUserId || '');
                        selectedUserName = payload.user_name || selectedUserName || '';
                        selectedUserEmail = payload.user_email || selectedUserEmail || '';
                        updateSelectedUserLabel();

                        if (closeThreadBtn) {
                            closeThreadBtn.classList.toggle('d-none', !selectedUserId);
                            closeThreadBtn.disabled = !selectedUserId;
                        }

                        if (overviewList && selectedUserId) {
                            const overviewItem = overviewList.querySelector('li[data-user-id="' + selectedUserId + '"]');
                            if (overviewItem) {
                                overviewItem.dataset.unread = '0';
                                overviewItem.dataset.userName = selectedUserName || '';
                                overviewItem.dataset.userEmail = selectedUserEmail || '';
                                const badge = overviewItem.querySelector('span.badge');
                                if (badge) {
                                    badge.className = 'badge rounded-pill bg-secondary-subtle text-secondary';
                                    badge.textContent = 'Sin nuevos';
                                }
                            }
                        }

                        updateAdminBadge(payload.total_unread);
                    } else if (selectedUserLabel) {
                        selectedUserLabel.textContent = 'Mensajes con el estudio';
                    }
                })
                .catch(function(err) {
                    showStatus(err.message, 'error');
                    if (role === 'admin') {
                        if (closeThreadBtn) {
                            closeThreadBtn.classList.add('d-none');
                            closeThreadBtn.disabled = true;
                        }
                        if (selectedUserLabel) {
                            selectedUserLabel.textContent = 'No se pudieron cargar los mensajes';
                        }
                    }
                })
                .finally(function() {
                    toggleLoading(false);
                });
        }

        function loadOverview() {
            if (!overviewPanel || !overviewList) {
                fetchThread(selectedUserId);
                return;
            }

            overviewList.innerHTML = '<li class="list-group-item text-muted">Cargando conversaciones...</li>';

            fetch(apiUrl, { credentials: 'same-origin' })
                .then(function(resp) {
                    if (!resp.ok) {
                        throw new Error('No se pudo obtener la lista de conversaciones');
                    }
                    return resp.json();
                })
                .then(function(payload) {
                    if (!payload.success || !Array.isArray(payload.overview)) {
                        throw new Error(payload.message || 'No hay conversaciones disponibles.');
                    }

                    updateAdminBadge(payload.total_unread);

                    overviewList.innerHTML = '';

                    if (!payload.overview.length) {
                        overviewList.innerHTML = '<li class="list-group-item text-muted">Sin conversaciones aún</li>';
                        renderMessages([]);
                        selectedUserId = 0;
                        selectedUserName = '';
                        selectedUserEmail = '';
                        wrapper.dataset.userId = '0';
                        updateSelectedUserLabel();
                        if (closeThreadBtn) {
                            closeThreadBtn.classList.add('d-none');
                            closeThreadBtn.disabled = true;
                        }
                        return;
                    }

                    const ordered = payload.overview.slice().sort(function(a, b) {
                        return (b.last_message_at || '').localeCompare(a.last_message_at || '');
                    });

                    ordered.forEach(function(item) {
                        const li = document.createElement('li');
                        li.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-start gap-2';
                        li.dataset.userId = item.user_id;
                        li.dataset.userName = item.user_name || '';
                        li.dataset.userEmail = item.user_email || '';
                        li.dataset.unread = item.unread_for_admin || 0;

                        const wrapperDiv = document.createElement('div');
                        wrapperDiv.className = 'me-auto';
                        const pieces = [
                            '<div class="fw-semibold">' + escapeHtml(item.user_name || ('Cliente #' + item.user_id)) + '</div>',
                            '<small class="text-muted">Último: ' + formatDate(item.last_message_at) + '</small>'
                        ];
                        if (item.user_email) {
                            pieces.push('<div class="small text-muted">' + escapeHtml(item.user_email) + '</div>');
                        }
                        wrapperDiv.innerHTML = pieces.join('');

                        const badge = document.createElement('span');
                        const unread = Number(item.unread_for_admin || 0);
                        badge.className = 'badge rounded-pill ' + (unread > 0 ? 'bg-danger' : 'bg-secondary-subtle text-secondary');
                        badge.textContent = unread > 0 ? unread + (unread === 1 ? ' nuevo' : ' nuevos') : 'Sin nuevos';

                        li.appendChild(wrapperDiv);
                        li.appendChild(badge);
                        li.addEventListener('click', function() {
                            selectedUserId = Number(this.dataset.userId || '0');
                            selectedUserName = this.dataset.userName || '';
                            selectedUserEmail = this.dataset.userEmail || '';
                            wrapper.dataset.userId = String(selectedUserId || '');
                            overviewList.querySelectorAll('.active').forEach(function(activeItem) {
                                activeItem.classList.remove('active');
                            });
                            this.classList.add('active');
                            updateSelectedUserLabel();
                            fetchThread(selectedUserId);
                        });

                        if (selectedUserId === item.user_id) {
                            li.classList.add('active');
                            selectedUserName = item.user_name || selectedUserName;
                            selectedUserEmail = item.user_email || selectedUserEmail;
                        }

                        overviewList.appendChild(li);
                    });

                    if (!selectedUserId && ordered[0]) {
                        selectedUserId = ordered[0].user_id;
                        selectedUserName = ordered[0].user_name || '';
                        selectedUserEmail = ordered[0].user_email || '';
                        wrapper.dataset.userId = String(selectedUserId);
                        const firstItem = overviewList.querySelector('li[data-user-id="' + selectedUserId + '"]');
                        if (firstItem) {
                            firstItem.classList.add('active');
                        }
                    }

                    updateSelectedUserLabel();
                    if (closeThreadBtn) {
                        closeThreadBtn.classList.toggle('d-none', !selectedUserId);
                        closeThreadBtn.disabled = !selectedUserId;
                    }

                    if (selectedUserId) {
                        fetchThread(selectedUserId);
                    } else {
                        renderMessages([]);
                    }
                })
                .catch(function(err) {
                    overviewList.innerHTML = '<li class="list-group-item text-danger">' + escapeHtml(err.message) + '</li>';
                    if (role === 'admin') {
                        selectedUserId = 0;
                        selectedUserName = '';
                        selectedUserEmail = '';
                        updateSelectedUserLabel();
                        if (closeThreadBtn) {
                            closeThreadBtn.classList.add('d-none');
                            closeThreadBtn.disabled = true;
                        }
                    }
                });
        }

        function submitMessage(evt) {
            evt.preventDefault();
            if (!form || !messageInput || !messageInput.value.trim()) {
                showStatus('Escribe un mensaje antes de enviarlo.', 'warning');
                return;
            }

            const payload = {
                message: messageInput.value.trim(),
                subject: subjectInput ? subjectInput.value.trim() : '',
                channel: 'soporte'
            };

            if (role === 'admin') {
                payload.user_id = hiddenUserIdInput ? parseInt(hiddenUserIdInput.value || '0', 10) : selectedUserId;
                if (!payload.user_id) {
                    showStatus('Selecciona una conversación para responder.', 'warning');
                    return;
                }
            }

            form.classList.add('is-processing');
            showStatus('', 'muted');

            fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify(payload)
            })
                .then(function(resp) {
                    if (!resp.ok) {
                        throw new Error('No se pudo enviar el mensaje.');
                    }
                    return resp.json();
                })
                .then(function(response) {
                    if (!response.success) {
                        throw new Error(response.message || 'No se pudo enviar el mensaje.');
                    }

                    messageInput.value = '';
                    if (subjectInput) {
                        subjectInput.value = '';
                    }

                    showStatus('Mensaje enviado correctamente.', 'success');

                    const targetId = role === 'admin'
                        ? (hiddenUserIdInput ? parseInt(hiddenUserIdInput.value || '0', 10) : selectedUserId)
                        : selectedUserId;

                    fetchThread(targetId);

                    if (role === 'admin') {
                        updateAdminBadge(response.total_unread);
                        loadOverview();
                    }
                })
                .catch(function(err) {
                    showStatus(err.message, 'error');
                })
                .finally(function() {
                    form.classList.remove('is-processing');
                });
        }

        function closeThread() {
            if (role !== 'admin' || !closeThreadBtn) {
                return;
            }
            if (!selectedUserId) {
                showStatus('Selecciona una conversación para finalizarla.', 'warning');
                return;
            }
            const display = selectedUserName || ('cliente #' + selectedUserId);
            if (!window.confirm('¿Finalizar la conversación con ' + display + '?')) {
                return;
            }

            toggleLoading(true);
            showStatus('', 'muted');

            fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({ action: 'close_thread', user_id: selectedUserId })
            })
                .then(function(resp) {
                    if (!resp.ok) {
                        throw new Error('No se pudo finalizar la conversación.');
                    }
                    return resp.json();
                })
                .then(function(response) {
                    if (!response.success) {
                        throw new Error(response.message || 'No se pudo finalizar la conversación.');
                    }

                    showStatus('Conversación finalizada.', 'success');

                    selectedUserId = 0;
                    selectedUserName = '';
                    selectedUserEmail = '';
                    wrapper.dataset.userId = '0';

                    renderMessages([]);
                    updateSelectedUserLabel();
                    if (closeThreadBtn) {
                        closeThreadBtn.classList.add('d-none');
                        closeThreadBtn.disabled = true;
                    }

                    if (overviewList) {
                        overviewList.innerHTML = '<li class="list-group-item text-muted">Cargando conversaciones...</li>';
                    }

                    updateAdminBadge(response.total_unread);
                    loadOverview();
                })
                .catch(function(err) {
                    showStatus(err.message, 'error');
                })
                .finally(function() {
                    toggleLoading(false);
                });
        }

        if (form) {
            form.addEventListener('submit', submitMessage);
        }

        if (closeThreadBtn) {
            closeThreadBtn.addEventListener('click', closeThread);
        }

        if (refreshBtn) {
            refreshBtn.addEventListener('click', function() {
                if (role === 'admin') {
                    loadOverview();
                } else {
                    fetchThread(selectedUserId);
                }
            });
        }

        if (role === 'admin') {
            loadOverview();
        } else {
            fetchThread(selectedUserId);
            if (pollInterval) {
                clearInterval(pollInterval);
            }
            pollInterval = window.setInterval(function() {
                fetchThread(selectedUserId);
            }, 60000);
        }

        window.addEventListener('beforeunload', function() {
            if (pollInterval) {
                clearInterval(pollInterval);
            }
        });
    });
})();
