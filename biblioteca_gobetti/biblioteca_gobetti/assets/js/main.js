/**
 * Biblioteca Gobetti - JavaScript Principale
 */

// Funzioni Modali
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
    }
}

// Chiudi modal cliccando fuori
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('active');
    }
});

// Funzione per conferma azioni
function confermaAzione(messaggio) {
    return confirm(messaggio);
}

// Ricerca in tempo reale
function setupLiveSearch(inputId, targetSelector, searchKey) {
    const input = document.getElementById(inputId);
    if (!input) return;
    
    input.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const items = document.querySelectorAll(targetSelector);
        
        items.forEach(item => {
            const text = item.getAttribute('data-' + searchKey) || item.textContent;
            if (text.toLowerCase().includes(searchTerm)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });
}

// Validazione form
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;
    
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            field.style.borderColor = '#e74c3c';
        } else {
            field.style.borderColor = '#bdc3c7';
        }
    });
    
    if (!isValid) {
        alert('Compila tutti i campi obbligatori');
    }
    
    return isValid;
}

// API Calls
async function apiCall(url, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
        }
    };
    
    if (data) {
        options.body = JSON.stringify(data);
    }
    
    try {
        const response = await fetch(url, options);
        return await response.json();
    } catch (error) {
        console.error('Errore API:', error);
        return { success: false, error: error.message };
    }
}

// Prenotazione libro
async function prenotaLibro(libroId, tipoPrenotazione = 'personale', classeId = null, studentiIds = []) {
    if (!confermaAzione('Confermi la prenotazione?')) return;
    
    const data = {
        libro_id: libroId,
        tipo_prenotazione: tipoPrenotazione,
        classe_id: classeId,
        studenti_ids: studentiIds
    };
    
    const result = await apiCall('/biblioteca_gobetti/api/prenota.php', 'POST', data);
    
    if (result.success) {
        alert('Prenotazione effettuata con successo!');
        location.reload();
    } else {
        alert('Errore: ' + result.message);
    }
}

// Annulla prenotazione
async function annullaPrenotazione(prenotazioneId) {
    if (!confermaAzione('Sei sicuro di voler annullare questa prenotazione?')) return;
    
    const result = await apiCall(`/biblioteca_gobetti/api/annulla_prenotazione.php?id=${prenotazioneId}`, 'DELETE');
    
    if (result.success) {
        alert('Prenotazione annullata');
        location.reload();
    } else {
        alert('Errore: ' + result.message);
    }
}

// Richiedi notifica disponibilità
async function richiediNotifica(libroId) {
    const result = await apiCall('/biblioteca_gobetti/api/richiedi_notifica.php', 'POST', { libro_id: libroId });
    
    if (result.success) {
        alert('Ti avviseremo quando il libro sarà disponibile!');
        // Aggiorna UI
        const btn = document.querySelector(`[data-libro-id="${libroId}"]`);
        if (btn) {
            btn.textContent = '✓ Riceverai una notifica';
            btn.disabled = true;
            btn.classList.remove('btn-warning');
            btn.classList.add('btn-secondary');
        }
    } else {
        alert('Errore: ' + result.message);
    }
}

// Filtri ricerca libri
function applicaFiltri() {
    const tipo = document.getElementById('filtro_tipo')?.value || '';
    const genere = document.getElementById('filtro_genere')?.value || '';
    const disponibilita = document.getElementById('filtro_disponibilita')?.value || '';
    
    const cards = document.querySelectorAll('.book-card');
    
    cards.forEach(card => {
        const cardTipo = card.getAttribute('data-tipo');
        const cardGenere = card.getAttribute('data-genere');
        const cardDisponibile = card.getAttribute('data-disponibile');
        
        let show = true;
        
        if (tipo && cardTipo !== tipo) show = false;
        if (genere && cardGenere !== genere) show = false;
        if (disponibilita) {
            if (disponibilita === 'si' && cardDisponibile !== '1') show = false;
            if (disponibilita === 'no' && cardDisponibile === '1') show = false;
        }
        
        card.style.display = show ? '' : 'none';
    });
}

// Setup filtri se presenti
document.addEventListener('DOMContentLoaded', function() {
    const filtroTipo = document.getElementById('filtro_tipo');
    const filtroGenere = document.getElementById('filtro_genere');
    const filtroDisponibilita = document.getElementById('filtro_disponibilita');
    
    if (filtroTipo) filtroTipo.addEventListener('change', applicaFiltri);
    if (filtroGenere) filtroGenere.addEventListener('change', applicaFiltri);
    if (filtroDisponibilita) filtroDisponibilita.addEventListener('change', applicaFiltri);
    
    // Setup ricerca live
    setupLiveSearch('search-input', '.book-card', 'search');
});

// Doppio check ritiro/restituzione
async function confermaRitiro(prestitoId, tipo) {
    const checkbox = document.getElementById(`check_${tipo}_${prestitoId}`);
    if (!checkbox || !checkbox.checked) {
        alert('Devi confermare il check prima di procedere');
        return;
    }
    
    if (!confermaAzione(`Confermi il ${tipo} del prestito?`)) {
        checkbox.checked = false;
        return;
    }
    
    const result = await apiCall('/biblioteca_gobetti/api/conferma_prestito.php', 'POST', {
        prestito_id: prestitoId,
        tipo: tipo
    });
    
    if (result.success) {
        alert('Operazione confermata');
        location.reload();
    } else {
        alert('Errore: ' + result.message);
        checkbox.checked = false;
    }
}

// Gestione selezione studenti per prenotazione di classe
function toggleStudentePrenotazione(checkbox) {
    const studenteId = checkbox.value;
    const lista = document.getElementById('studenti_selezionati');
    if (!lista) return;
    
    let studenti = lista.value ? JSON.parse(lista.value) : [];
    
    if (checkbox.checked) {
        if (!studenti.includes(studenteId)) {
            studenti.push(studenteId);
        }
    } else {
        studenti = studenti.filter(id => id !== studenteId);
    }
    
    lista.value = JSON.stringify(studenti);
    
    // Aggiorna contatore
    const contatore = document.getElementById('contatore_studenti');
    if (contatore) {
        contatore.textContent = studenti.length;
    }
}

// Seleziona/deseleziona tutti gli studenti
function toggleTuttiStudenti(checkbox) {
    const checkboxes = document.querySelectorAll('.studente-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = checkbox.checked;
        toggleStudentePrenotazione(cb);
    });
}

// Toast notification
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type}`;
    toast.textContent = message;
    toast.style.position = 'fixed';
    toast.style.top = '20px';
    toast.style.right = '20px';
    toast.style.zIndex = '9999';
    toast.style.minWidth = '300px';
    toast.style.animation = 'slideInRight 0.3s ease-out';
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOutRight 0.3s ease-out';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Aggiorna timer countdown per scadenze
function aggiornaCountdown() {
    const countdowns = document.querySelectorAll('[data-countdown]');
    
    countdowns.forEach(element => {
        const targetDate = new Date(element.getAttribute('data-countdown'));
        const now = new Date();
        const diff = targetDate - now;
        
        if (diff <= 0) {
            element.textContent = 'Scaduto';
            element.style.color = '#e74c3c';
            return;
        }
        
        const giorni = Math.floor(diff / (1000 * 60 * 60 * 24));
        const ore = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minuti = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        
        if (giorni > 0) {
            element.textContent = `${giorni}g ${ore}h`;
        } else if (ore > 0) {
            element.textContent = `${ore}h ${minuti}m`;
        } else {
            element.textContent = `${minuti}m`;
        }
        
        // Cambia colore in base al tempo rimanente
        if (giorni < 1) {
            element.style.color = '#e74c3c';
        } else if (giorni < 3) {
            element.style.color = '#f39c12';
        }
    });
}

// Avvia countdown se ci sono elementi
if (document.querySelectorAll('[data-countdown]').length > 0) {
    aggiornaCountdown();
    setInterval(aggiornaCountdown, 60000); // Aggiorna ogni minuto
}

// Anteprima immagine upload
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (!preview) return;
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Export funzioni globali
window.bibliotecaGobetti = {
    openModal,
    closeModal,
    prenotaLibro,
    annullaPrenotazione,
    richiediNotifica,
    confermaRitiro,
    toggleStudentePrenotazione,
    toggleTuttiStudenti,
    showToast,
    previewImage,
    validateForm
};
