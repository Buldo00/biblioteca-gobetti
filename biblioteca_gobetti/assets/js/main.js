/**
 * Biblioteca Gobetti - JavaScript principale
 * Vanilla JS, compatibile jQuery se presente
 */

(function () {
    'use strict';

    /* ============================================================
       INITIALIZATION
       ============================================================ */
    document.addEventListener('DOMContentLoaded', function () {
        initHamburgerMenu();
        initModals();
        initAlerts();
        initPasswordToggles();
        initPasswordCopyPrevention();
        initSearchBoxes();
        initFormValidation();
        initTabs();
    });

    /* ============================================================
       HAMBURGER MENU
       ============================================================ */
    function initHamburgerMenu() {
        var hamburgerBtn = document.getElementById('hamburgerBtn');
        var mainNav = document.getElementById('mainNav');

        if (!hamburgerBtn || !mainNav) return;

        hamburgerBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            var isOpen = mainNav.classList.toggle('open');
            hamburgerBtn.setAttribute('aria-expanded', isOpen);
            hamburgerBtn.innerHTML = isOpen
                ? '<i class="fas fa-times"></i>'
                : '<i class="fas fa-bars"></i>';
        });

        // Close nav when clicking a link (mobile)
        mainNav.querySelectorAll('.nav-link').forEach(function (link) {
            link.addEventListener('click', function () {
                mainNav.classList.remove('open');
                hamburgerBtn.innerHTML = '<i class="fas fa-bars"></i>';
                hamburgerBtn.setAttribute('aria-expanded', 'false');
            });
        });

        // Close nav when clicking outside
        document.addEventListener('click', function (e) {
            if (mainNav.classList.contains('open') &&
                !mainNav.contains(e.target) &&
                !hamburgerBtn.contains(e.target)) {
                mainNav.classList.remove('open');
                hamburgerBtn.innerHTML = '<i class="fas fa-bars"></i>';
                hamburgerBtn.setAttribute('aria-expanded', 'false');
            }
        });

        // Close on Escape
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && mainNav.classList.contains('open')) {
                mainNav.classList.remove('open');
                hamburgerBtn.innerHTML = '<i class="fas fa-bars"></i>';
                hamburgerBtn.setAttribute('aria-expanded', 'false');
                hamburgerBtn.focus();
            }
        });
    }

    /* ============================================================
       MODALS
       ============================================================ */
    function initModals() {
        // Close buttons inside modals
        document.querySelectorAll('.modal-close, [data-dismiss="modal"]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var overlay = btn.closest('.modal-overlay');
                if (overlay) closeModal(overlay.id);
            });
        });

        // Close on outside click
        document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) {
                    closeModal(overlay.id);
                }
            });
        });

        // Close on Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                var activeModal = document.querySelector('.modal-overlay.active');
                if (activeModal) closeModal(activeModal.id);
            }
        });

        // Open buttons
        document.querySelectorAll('[data-modal]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                openModal(btn.getAttribute('data-modal'));
            });
        });
    }

    /**
     * Open a modal by its overlay ID
     */
    window.openModal = function (modalId) {
        var overlay = document.getElementById(modalId);
        if (!overlay) return;
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';

        // Focus first focusable element
        var focusable = overlay.querySelector('input:not([type="hidden"]), select, textarea, button:not(.modal-close)');
        if (focusable) {
            setTimeout(function () { focusable.focus(); }, 100);
        }
    };

    /**
     * Close a modal by its overlay ID
     */
    window.closeModal = function (modalId) {
        var overlay = document.getElementById(modalId);
        if (!overlay) return;
        overlay.classList.remove('active');

        // Restore body scroll if no other modals are open
        if (!document.querySelector('.modal-overlay.active')) {
            document.body.style.overflow = '';
        }
    };

    /**
     * Close all open modals
     */
    window.closeAllModals = function () {
        document.querySelectorAll('.modal-overlay.active').forEach(function (overlay) {
            overlay.classList.remove('active');
        });
        document.body.style.overflow = '';
    };

    /* ============================================================
       CONFIRMATION DIALOGS
       ============================================================ */

    /**
     * Show a confirmation dialog before proceeding
     * @param {string} message - Confirmation message
     * @param {Function} onConfirm - Callback if confirmed
     * @param {Function} [onCancel] - Callback if cancelled
     */
    window.confirmAction = function (message, onConfirm, onCancel) {
        // Use a custom modal if available, else browser confirm
        var existingModal = document.getElementById('confirmModal');
        if (existingModal) {
            var msgEl = existingModal.querySelector('.confirm-message');
            var btnConfirm = existingModal.querySelector('.confirm-yes');
            var btnCancel = existingModal.querySelector('.confirm-no');

            if (msgEl) msgEl.textContent = message;

            // Clean up old listeners
            var newBtnConfirm = btnConfirm.cloneNode(true);
            var newBtnCancel = btnCancel.cloneNode(true);
            btnConfirm.parentNode.replaceChild(newBtnConfirm, btnConfirm);
            btnCancel.parentNode.replaceChild(newBtnCancel, btnCancel);

            newBtnConfirm.addEventListener('click', function () {
                closeModal('confirmModal');
                if (typeof onConfirm === 'function') onConfirm();
            });

            newBtnCancel.addEventListener('click', function () {
                closeModal('confirmModal');
                if (typeof onCancel === 'function') onCancel();
            });

            openModal('confirmModal');
        } else {
            if (confirm(message)) {
                if (typeof onConfirm === 'function') onConfirm();
            } else {
                if (typeof onCancel === 'function') onCancel();
            }
        }
    };

    /**
     * Attach confirmation to a link or form
     */
    window.confirmLink = function (element, message) {
        element.addEventListener('click', function (e) {
            e.preventDefault();
            var href = element.getAttribute('href');
            confirmAction(message || 'Sei sicuro?', function () {
                window.location.href = href;
            });
        });
    };

    // Auto-attach confirmation to elements with data-confirm
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-confirm]').forEach(function (el) {
            var msg = el.getAttribute('data-confirm');
            el.addEventListener('click', function (e) {
                e.preventDefault();
                confirmAction(msg, function () {
                    if (el.tagName === 'A') {
                        window.location.href = el.href;
                    } else if (el.type === 'submit') {
                        el.closest('form').submit();
                    }
                });
            });
        });
    });

    /* ============================================================
       ALERTS
       ============================================================ */
    function initAlerts() {
        document.querySelectorAll('.alert .close-alert').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var alert = btn.closest('.alert');
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                alert.style.transition = 'all 0.3s ease';
                setTimeout(function () { alert.remove(); }, 300);
            });
        });

        // Auto-dismiss success alerts after 5 seconds
        document.querySelectorAll('.alert-success[data-auto-dismiss]').forEach(function (alert) {
            setTimeout(function () {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                alert.style.transition = 'all 0.3s ease';
                setTimeout(function () { alert.remove(); }, 300);
            }, 5000);
        });
    }

    /* ============================================================
       PASSWORD VISIBILITY TOGGLE
       ============================================================ */
    function initPasswordToggles() {
        document.querySelectorAll('.password-toggle').forEach(function (btn) {
            var wrapper = btn.closest('.password-wrapper');
            if (!wrapper) return;
            var input = wrapper.querySelector('input');
            if (!input) return;

            // Click toggle
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                togglePasswordVisibility(input, btn);
            });

            // Hold mode: show while pressed, hide on release
            btn.addEventListener('mousedown', function (e) {
                if (e.button !== 0) return;
                input.type = 'text';
                btn.classList.add('active');
                btn.innerHTML = '<i class="fas fa-eye-slash"></i>';
            });

            btn.addEventListener('mouseup', function () {
                input.type = 'password';
                btn.classList.remove('active');
                btn.innerHTML = '<i class="fas fa-eye"></i>';
            });

            btn.addEventListener('mouseleave', function () {
                input.type = 'password';
                btn.classList.remove('active');
                btn.innerHTML = '<i class="fas fa-eye"></i>';
            });

            // Touch support
            btn.addEventListener('touchstart', function (e) {
                e.preventDefault();
                input.type = 'text';
                btn.classList.add('active');
                btn.innerHTML = '<i class="fas fa-eye-slash"></i>';
            });

            btn.addEventListener('touchend', function () {
                input.type = 'password';
                btn.classList.remove('active');
                btn.innerHTML = '<i class="fas fa-eye"></i>';
            });
        });
    }

    function togglePasswordVisibility(input, btn) {
        var isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        btn.classList.toggle('active', isPassword);
        btn.innerHTML = isPassword
            ? '<i class="fas fa-eye-slash"></i>'
            : '<i class="fas fa-eye"></i>';
    }

    /* ============================================================
       COPY-PASTE PREVENTION ON PASSWORD FIELDS
       ============================================================ */
    function initPasswordCopyPrevention() {
        document.querySelectorAll('input[type="password"], input[data-no-paste]').forEach(function (input) {
            input.addEventListener('copy', function (e) { e.preventDefault(); });
            input.addEventListener('cut', function (e) { e.preventDefault(); });
            input.addEventListener('paste', function (e) { e.preventDefault(); });
        });
    }

    /* ============================================================
       LIVE SEARCH
       ============================================================ */
    function initSearchBoxes() {
        document.querySelectorAll('.search-box').forEach(function (box) {
            var input = box.querySelector('.form-control');
            var clearBtn = box.querySelector('.search-clear');
            if (!input) return;

            var debounceTimer = null;

            input.addEventListener('input', function () {
                if (clearBtn) {
                    clearBtn.classList.toggle('visible', input.value.length > 0);
                }

                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function () {
                    var event = new CustomEvent('liveSearch', {
                        detail: { query: input.value.trim() },
                        bubbles: true
                    });
                    input.dispatchEvent(event);

                    // If there's a target table, filter it
                    var targetId = input.getAttribute('data-search-target');
                    if (targetId) {
                        filterTable(targetId, input.value.trim());
                    }
                }, 300);
            });

            if (clearBtn) {
                clearBtn.addEventListener('click', function () {
                    input.value = '';
                    clearBtn.classList.remove('visible');
                    input.dispatchEvent(new Event('input'));
                    input.focus();
                });
            }
        });
    }

    /**
     * Filter a table's rows by search query
     */
    function filterTable(tableId, query) {
        var table = document.getElementById(tableId);
        if (!table) return;

        var rows = table.querySelectorAll('tbody tr');
        var lowerQuery = query.toLowerCase();
        var visibleCount = 0;

        rows.forEach(function (row) {
            if (row.classList.contains('empty-row')) return;
            var text = row.textContent.toLowerCase();
            var match = !query || text.indexOf(lowerQuery) !== -1;
            row.style.display = match ? '' : 'none';
            if (match) visibleCount++;
        });

        // Show/hide empty state
        var emptyRow = table.querySelector('.empty-row');
        if (emptyRow) {
            emptyRow.style.display = visibleCount === 0 ? '' : 'none';
        }
    }

    // Expose globally
    window.filterTable = filterTable;

    /* ============================================================
       FORM VALIDATION
       ============================================================ */
    function initFormValidation() {
        document.querySelectorAll('form[data-validate]').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                var isValid = true;

                // Clear previous errors
                form.querySelectorAll('.is-invalid').forEach(function (el) {
                    el.classList.remove('is-invalid');
                });
                form.querySelectorAll('.invalid-feedback').forEach(function (el) {
                    el.remove();
                });

                // Check required fields
                form.querySelectorAll('[required]').forEach(function (input) {
                    if (!input.value.trim()) {
                        isValid = false;
                        showFieldError(input, 'Campo obbligatorio');
                    }
                });

                // Check email fields
                form.querySelectorAll('input[type="email"]').forEach(function (input) {
                    if (input.value && !isValidEmail(input.value)) {
                        isValid = false;
                        showFieldError(input, 'Email non valida');
                    }
                });

                // Check min-length
                form.querySelectorAll('[data-minlength]').forEach(function (input) {
                    var minLen = parseInt(input.getAttribute('data-minlength'), 10);
                    if (input.value && input.value.length < minLen) {
                        isValid = false;
                        showFieldError(input, 'Minimo ' + minLen + ' caratteri');
                    }
                });

                // Check password match
                var password = form.querySelector('[data-match]');
                if (password) {
                    var matchId = password.getAttribute('data-match');
                    var matchInput = form.querySelector('#' + matchId);
                    if (matchInput && password.value !== matchInput.value) {
                        isValid = false;
                        showFieldError(password, 'Le password non corrispondono');
                    }
                }

                if (!isValid) {
                    e.preventDefault();
                    var firstInvalid = form.querySelector('.is-invalid');
                    if (firstInvalid) firstInvalid.focus();
                }
            });
        });
    }

    function showFieldError(input, message) {
        input.classList.add('is-invalid');
        var feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        feedback.textContent = message;
        input.parentNode.appendChild(feedback);
    }

    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    /* ============================================================
       TABS
       ============================================================ */
    function initTabs() {
        document.querySelectorAll('.tabs').forEach(function (tabContainer) {
            tabContainer.querySelectorAll('.tab-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var target = btn.getAttribute('data-tab');
                    var parent = tabContainer.closest('.card') || tabContainer.parentNode;

                    // Deactivate all tabs
                    tabContainer.querySelectorAll('.tab-btn').forEach(function (b) {
                        b.classList.remove('active');
                    });
                    parent.querySelectorAll('.tab-content').forEach(function (c) {
                        c.classList.remove('active');
                    });

                    // Activate selected tab
                    btn.classList.add('active');
                    var content = parent.querySelector('#' + target);
                    if (content) content.classList.add('active');
                });
            });
        });
    }

    /* ============================================================
       DYNAMIC CLASS / STUDENT SELECTION
       ============================================================ */

    /**
     * Load students for a given class via AJAX
     * @param {string} classeId - Class ID
     * @param {string} targetSelectId - Target <select> element ID
     * @param {string} [apiUrl] - API endpoint
     */
    window.loadStudentsByClass = function (classeId, targetSelectId, apiUrl) {
        var select = document.getElementById(targetSelectId);
        if (!select) return;

        select.innerHTML = '<option value="">Caricamento...</option>';
        select.disabled = true;

        var url = apiUrl || 'api/studenti_classe.php';
        ajaxGet(url + '?classe_id=' + encodeURIComponent(classeId), function (data) {
            select.innerHTML = '<option value="">-- Seleziona studente --</option>';
            if (data && data.studenti) {
                data.studenti.forEach(function (s) {
                    var opt = document.createElement('option');
                    opt.value = s.id;
                    opt.textContent = s.cognome + ' ' + s.nome;
                    select.appendChild(opt);
                });
            }
            select.disabled = false;
        }, function () {
            select.innerHTML = '<option value="">Errore caricamento</option>';
            select.disabled = false;
        });
    };

    /**
     * Initialize class-student cascading dropdowns
     */
    window.initClassStudentSelect = function (classeSelectId, studenteSelectId, apiUrl) {
        var classeSelect = document.getElementById(classeSelectId);
        if (!classeSelect) return;

        classeSelect.addEventListener('change', function () {
            var classeId = classeSelect.value;
            if (classeId) {
                loadStudentsByClass(classeId, studenteSelectId, apiUrl);
            } else {
                var studenteSelect = document.getElementById(studenteSelectId);
                if (studenteSelect) {
                    studenteSelect.innerHTML = '<option value="">-- Seleziona prima la classe --</option>';
                    studenteSelect.disabled = true;
                }
            }
        });
    };

    /* ============================================================
       AJAX HELPERS
       ============================================================ */

    /**
     * AJAX GET request
     */
    window.ajaxGet = function (url, onSuccess, onError) {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.onreadystatechange = function () {
            if (xhr.readyState !== 4) return;
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    if (typeof onSuccess === 'function') onSuccess(data);
                } catch (e) {
                    if (typeof onError === 'function') onError(e);
                }
            } else {
                if (typeof onError === 'function') onError(xhr);
            }
        };
        xhr.send();
    };

    /**
     * AJAX POST request
     */
    window.ajaxPost = function (url, data, onSuccess, onError) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', url, true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.onreadystatechange = function () {
            if (xhr.readyState !== 4) return;
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    var result = JSON.parse(xhr.responseText);
                    if (typeof onSuccess === 'function') onSuccess(result);
                } catch (e) {
                    if (typeof onError === 'function') onError(e);
                }
            } else {
                if (typeof onError === 'function') onError(xhr);
            }
        };
        xhr.send(JSON.stringify(data));
    };

    /**
     * Submit a form via AJAX
     */
    window.ajaxSubmitForm = function (formElement, onSuccess, onError) {
        var formData = new FormData(formElement);
        var xhr = new XMLHttpRequest();
        xhr.open(formElement.method || 'POST', formElement.action, true);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.onreadystatechange = function () {
            if (xhr.readyState !== 4) return;
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    var result = JSON.parse(xhr.responseText);
                    if (typeof onSuccess === 'function') onSuccess(result);
                } catch (e) {
                    if (typeof onError === 'function') onError(e);
                }
            } else {
                if (typeof onError === 'function') onError(xhr);
            }
        };
        xhr.send(formData);
    };

    /* ============================================================
       TOAST NOTIFICATIONS
       ============================================================ */

    /**
     * Show a toast notification
     * @param {string} message
     * @param {string} [type='info'] - 'success', 'danger', 'warning', 'info'
     * @param {number} [duration=4000] - Auto-close ms, 0 to disable
     */
    window.showToast = function (message, type, duration) {
        type = type || 'info';
        duration = duration !== undefined ? duration : 4000;

        var container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        var toast = document.createElement('div');
        toast.className = 'toast toast-' + type;

        var icons = {
            success: 'fa-check-circle',
            danger: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };

        toast.innerHTML =
            '<i class="fas ' + (icons[type] || icons.info) + '"></i>' +
            '<span>' + escapeHtml(message) + '</span>';

        container.appendChild(toast);

        if (duration > 0) {
            setTimeout(function () {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100%)';
                toast.style.transition = 'all 0.3s ease';
                setTimeout(function () { toast.remove(); }, 300);
            }, duration);
        }

        return toast;
    };

    /* ============================================================
       PRINT FUNCTIONS
       ============================================================ */

    /**
     * Print a label element
     */
    window.printLabel = function (elementId) {
        var el = document.getElementById(elementId);
        if (!el) return;

        var printWindow = window.open('', '_blank', 'width=400,height=300');
        printWindow.document.write(
            '<!DOCTYPE html><html><head><title>Stampa Etichetta</title>' +
            '<style>' +
            'body { font-family: monospace; margin: 10px; }' +
            '.label-title { font-weight: bold; border-bottom: 1px solid #000; padding-bottom: 4px; margin-bottom: 4px; }' +
            '.label-row { font-size: 11px; padding: 2px 0; }' +
            '.label-barcode { text-align: center; font-size: 18px; letter-spacing: 2px; border-top: 1px solid #000; margin-top: 4px; padding-top: 4px; }' +
            '</style></head><body>' +
            el.innerHTML +
            '</body></html>'
        );
        printWindow.document.close();
        printWindow.focus();
        setTimeout(function () {
            printWindow.print();
            printWindow.close();
        }, 250);
    };

    /**
     * Print current page content
     */
    window.printPage = function () {
        window.print();
    };

    /* ============================================================
       LOADING OVERLAY
       ============================================================ */

    window.showLoading = function (message) {
        var overlay = document.querySelector('.loading-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'loading-overlay';
            overlay.innerHTML =
                '<div class="spinner spinner-lg"></div>' +
                '<span class="loading-text">' + escapeHtml(message || 'Caricamento...') + '</span>';
            document.body.appendChild(overlay);
        } else {
            var textEl = overlay.querySelector('.loading-text');
            if (textEl) textEl.textContent = message || 'Caricamento...';
        }
        overlay.classList.add('active');
    };

    window.hideLoading = function () {
        var overlay = document.querySelector('.loading-overlay');
        if (overlay) overlay.classList.remove('active');
    };

    /* ============================================================
       UTILITY FUNCTIONS
       ============================================================ */

    /**
     * Escape HTML entities
     */
    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str || ''));
        return div.innerHTML;
    }

    window.escapeHtml = escapeHtml;

    /**
     * Format a date string for Italian locale
     */
    window.formatDate = function (dateStr) {
        if (!dateStr) return '';
        var d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString('it-IT', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    };

    /**
     * Debounce utility
     */
    window.debounce = function (fn, delay) {
        var timer = null;
        return function () {
            var context = this;
            var args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () {
                fn.apply(context, args);
            }, delay || 300);
        };
    };

})();
