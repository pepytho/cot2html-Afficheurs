var direction = 1;
var first_time = 1;
var burst_cnt = 0;
var pause_cnt = 100;
var stuck_count = 0;
var stuck_limit = 10;
var currentpos = 0;
var alt = 1;
var curpos1 = 0;
var curpos2 = -1;
var oldpos = 0;
var pause = 100;
var waitBeforeChange = 0;

var bdy;
var delta = 0;

/**
 * Parse a query string into an object
 * @param {string} queryString - The query string to parse
 * @return {object} Parsed query parameters
 */
function parseQuery(queryString) 
{
    var query = {};
    var pairs = (queryString[0] === '?' ? queryString.substr(1) : queryString).split('&');
    for (var i = 0; i < pairs.length; i++) {
        var pair = pairs[i].split('=');
        query[decodeURIComponent(pair[0])] = decodeURIComponent(pair[1] || '');
    }
    return query;
}

/**
 * Rebuild a URL with query parameters
 * @param {object} qry - The query parameters
 * @return {string} Full URL with query parameters
 */
function rebuildQuery(qry)
{
    // Forcer scroll=1 systématiquement
    qry["scroll"] = 1;
    // Utiliser la valeur de zoom courante (CSS ou cookie)
    var zoom = getZoom();
    if (!zoom || isNaN(zoom)) {
        zoom = getZoomCookie() || 1;
    }
    qry["zoom"] = zoom;
    var str = location.origin+location.pathname;
    var sep = "?";
    for (var key in qry) 
    {
        str = str + sep + key + "=" + qry[key];
        sep = "&";
    }
    return str;
}

/**
 * Get current zoom level from CSS variable
 * @return {string} Current zoom level
 */
function getZoom()
{
   return getComputedStyle(document.body).getPropertyValue('--rescale');
}

/**
 * Show UI controls on first interaction
 */
function mickey()
{
    if (first_time)
    {
        first_time = 0;
        return;
    }
    pause = 500;
    document.getElementById('autohide').style.visibility = 'visible';
}

/**
 * Handle scroll control actions
 * @param {number} v - Action type: 0=save params, 1=toggle scroll, 2=save zoom
 */
function scro(v)
{
    // enregistrer zoom, tabStart et tabEnd
    if (v === 0) {
        var zoom = getZoom();
        var qry  = parseQuery(location.search);
        var pageKey  = qry["item"]     || null;
        var tabStart = qry["tabStart"] || null;
        var tabEnd   = qry["tabEnd"]   || null;
        updateCookies(zoom, tabStart, tabEnd, pageKey);
    }

    if (v == 2)
    {
        var qry = parseQuery(location.search); 
        qry["zoom"] = getZoom();
        location.replace(rebuildQuery(qry));
    }
    if (v == 1)
    {
        var qry = parseQuery(location.search); 
        qry["scroll"] = (qry["scroll"] == 0) ? 1 : 0;
        qry["zoom"] = getZoom();
        console.log("Toggling scroll to", qry["scroll"]); // Debugging log
        location.replace(rebuildQuery(qry));
    }
    pause = 0;
    document.getElementById('autohide').style.visibility = 'hidden';
}

/**
 * Auto-scroll the window content for specific arrays and refresh the page when finished
 */
function scrollwindow() {
    if (pauseTopBottom > 0) {
        pauseTopBottom--;
        return;
    }
    if (!bdy) {
        bdy = document.getElementById('scrollme');
        if (!bdy) {
            return;
        }
    }
    // Exclude 'tab' from old scrollwindow system - it uses the new scroll-refresh.js system
    if (!['lst', 'pou', 'poudet', 'clapou', 'clatab', 'finalcla'].includes(getCurrentArray())) {
        return;
    }
    if (pause > 0) {
        pause--;
    } else {
        if (pause_cnt > 0) {
            pause_cnt--;
        } else {
            if (burst_cnt > 0) {
                burst_cnt--;
                pause_cnt = intra_burst_delay;
            } else {
                burst_cnt = glob_burst_length;
                pause_cnt = extra_burst_delay;
            }
            var old = bdy.scrollTop;
            var maxScroll = bdy.scrollHeight - bdy.clientHeight;
            var dst = old + delta + direction * speed;
            // Si on atteint le bas, on inverse la direction et on pause 8s
            if (dst >= maxScroll) {
                dst = maxScroll;
                direction = -1;
                pauseTopBottom = Math.round(8000 / burst_timer); // 8s en ticks
            }
            // Si on atteint le haut, on inverse la direction et on pause 8s
            if (dst <= 0) {
                dst = 0;
                direction = 1;
                pauseTopBottom = Math.round(8000 / burst_timer); // 8s en ticks
            }
            bdy.scrollTop = dst;
            delta = dst - bdy.scrollTop;
            if (old === bdy.scrollTop) {
                stuck_count++;
            } else {
                stuck_count = 0;
            }
            if (stuck_count > stuck_limit) {
                location.reload();
            }
        }
    }
}

/**
 * Get the current array being displayed
 * @return {string} The current array name
 */
function getCurrentArray() {
    var qry = parseQuery(location.search);
    return qry["item"] || '';
}

/**
 * Set CSS rescale variable for zooming
 * @param {number} z - Zoom level
 */
let root = document.documentElement;
function rescale(z)
{
    root.style.setProperty('--rescale', z);
}

/**
 * Set tab start parameter and navigate
 * @param {number} x - Tab start value
 */
function tabStart(x)
{
    var qry = parseQuery(location.search); 
    qry["tabStart"] = x;
    qry["zoom"] = getZoom();
    location.replace(rebuildQuery(qry));
}

/**
 * Set item parameter and navigate
 * @param {string} x - Item value
 */
function item(x)
{
    var qry = parseQuery(location.search);
    qry["item"] = x;
    // si on va sur le tableau, on remet à leurs valeurs par défaut
    if (x === "tab") {
        qry["tabStart"] = 256;
        qry["tabEnd"]   = 2;
    }
    qry["zoom"] = getZoom();
    location.replace(rebuildQuery(qry));
}

/**
 * Set fold parameter and navigate
 * @param {number} x - Fold value
 */
function fold(x)
{
    var qry = parseQuery(location.search); 
    qry["fold"] = x;
    qry["zoom"] = getZoom();
    location.replace(rebuildQuery(qry));
}

/**
 * Set tab end parameter and navigate
 * @param {number} x - Tab end value
 */
function tabEnd(x)
{
    var qry = parseQuery(location.search); 
    qry["tabEnd"] = x;
    qry["zoom"] = getZoom();
    location.replace(rebuildQuery(qry));
}

/**
 * Initialize scrolling and UI with default autoscroll enabled
 * @param {boolean} scroll - Whether to enable scrolling
 */
function startit(scroll = true)
{
    console.log("Initializing autoscroll with scroll =", scroll);

    ban = document.getElementById('scrollme');
    if (ban != null) {
        ban.onclick = mickey;
    }
    
    bdy = document.getElementById('scrollme');
    if (bdy != null && scroll) {
        console.log("Starting autoscroll interval");
        setInterval(scrollwindow, burst_timer);
        
        // Check if this is a tab page - use new scroll system instead of old one
        var urlParams = new URLSearchParams(window.location.search);
        var item = urlParams.get('item');
        if (item === 'tab') {
            console.log("Tab page detected - initializing new scroll system");
            if (typeof window.initAutoScroll === 'function') {
                setTimeout(() => {
                    window.initAutoScroll('scrollme');
                }, 200);
            }
            // Still start the old system for other pages, but it will be filtered out in scrollwindow()
        }
    } else {
        console.log("Autoscroll not started: bdy =", bdy, "scroll =", scroll);
    }
}

// Initialize zoom slider if present
var zoomSlider = document.getElementById('slider-zoom');
if (zoomSlider != null) {
    noUiSlider.create(zoomSlider, {
        start: [getZoom()],
        range: {
            'min': [0.01],
            'max': [4.00]
        }
    });

    // When the slider value changes, update the zoom
    zoomSlider.noUiSlider.on('update', function(values, handle) {
        var v = values[handle];
        rescale(v);
    });
}

/**
 * Save a parameter to a cookie
 * @param {string} name - The name of the parameter
 * @param {string|number} value - The value of the parameter
 * @param {string} [pageKey] - Optional page-specific key for zoom
 */
function saveParameterToCookie(name, value, pageKey = null) {
    if (pageKey && name === 'zoom') {
        name = `zoom_${pageKey}`;
    }
    document.cookie = `${name}=${value}; path=/; max-age=3600`; // Expires in 1 hour
}

/**
 * Update zoom, tabStart, and tabEnd cookies
 * @param {number} zoom - The zoom level
 * @param {number} tabStart - The tabStart value
 * @param {number} tabEnd - The tabEnd value
 * @param {string} pageKey - Page-specific key for zoom
 */
function updateCookies(zoom, tabStart, tabEnd, pageKey) {
    saveParameterToCookie('zoom', zoom, pageKey);
    saveParameterToCookie('tabStart', tabStart);
    saveParameterToCookie('tabEnd', tabEnd);
}

/**
 * Navigate to the "Classement Final" page
 */
function goToFinalPage() {
    var qry = parseQuery(location.search);
    qry["item"] = "finalcla";
    qry["zoom"] = getZoom();
    location.replace(rebuildQuery(qry));
}

/**
 * Change the selected SuiteDeTableaux and navigate
 * @param {string} suiteId - The ID of the selected suite
 */
function changeSuite(suiteId) {
    var qry = parseQuery(location.search);
    qry["suite"] = suiteId;
    qry["zoom"] = getZoom();
    
    // Build URL manually to avoid rebuildQuery forcing scroll=1
    var str = location.origin + location.pathname;
    var sep = "?";
    for (var key in qry) {
        str = str + sep + key + "=" + encodeURIComponent(qry[key]);
        sep = "&";
    }
    
    console.log("Changing suite to:", suiteId, "URL:", str);
    location.replace(str);
}

/**
 * Add region and Categorie variables to the final list if available
 * @param {object} finalList - The final list object
 * @param {string} region - The region variable
 * @param {string} categorie - The Categorie variable
 */
function addRegionAndCategorie(finalList, region, categorie) {
    if (region) {
        finalList.region = region;
    }
    if (categorie) {
        finalList.Categorie = categorie;
    }
}

// Example usage for finalcla
var finalList = {}; // Assuming this is the final list object
addRegionAndCategorie(finalList, 'RegionName', 'CategorieName');

// Ajout minimal pour synchroniser le zoom affiché avec l'URL ou le cookie
function getZoomFromUrlOrCookie() {
    var qry = parseQuery(location.search);
    if (qry["zoom"] && !isNaN(qry["zoom"])) {
        return parseFloat(qry["zoom"]);
    }
    var z = getZoomCookie && getZoomCookie();
    if (z && !isNaN(z)) return z;
    return 1;
}

function applyZoomFromUrlOrCookie() {
    var zoom = getZoomFromUrlOrCookie();
    rescale(zoom);
    var zoomSlider = document.getElementById('slider-zoom');
    if (zoomSlider && zoomSlider.noUiSlider) {
        zoomSlider.noUiSlider.set(zoom);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    applyZoomFromUrlOrCookie();
    
    // Ouvrir le panneau de contrôle au clic sur le bouton Outils
    var toolsBtn = document.getElementById('tools-button');
    if (toolsBtn) {
        toolsBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            var panel = document.getElementById('autohide');
            if (panel) {
                panel.style.visibility = panel.style.visibility === 'visible' ? 'hidden' : 'visible';
            }
        });
    }

    // Prevent suite selector from triggering autohide panel
    var suiteSelector = document.getElementById('suiteSelector');
    if (suiteSelector) {
        suiteSelector.addEventListener('click', function(e) {
            e.stopPropagation();
        });
        
        suiteSelector.addEventListener('change', function(e) {
            e.stopPropagation();
        });
    }

    var tbl = document.getElementById('tableauRangeSelector');
    if (!tbl || typeof noUiSlider === 'undefined') return;

    // Récupérer les valeurs de l'URL ou utiliser les valeurs par défaut
    var qry = parseQuery(location.search);
    var ts = parseInt(qry['tabStart']) || 512; // Valeur par défaut 512
    var te = parseInt(qry['tabEnd']) || 2;     // Valeur par défaut 2

    // Définir les valeurs autorisées dans l'ordre décroissant
    var allowedValues = [512, 256, 128, 64, 32, 16, 8, 4, 2];

    // Variables pour stocker les valeurs temporaires
    var tempTabStart = ts;
    var tempTabEnd = te;

    // Créer les menus déroulants
    tbl.innerHTML = `
        <div style="display: flex; gap: 10px; align-items: center; margin: 10px 0;">
            <div style="flex: 1;">
                <label for="tabStartSelect" style="display: block; margin-bottom: 5px; color: #00917B;">TabStart</label>
                <select id="tabStartSelect" style="width: 100%; padding: 5px; border: 2px solid #00917B; border-radius: 4px; background: #42FEDC;">
                    ${allowedValues.map(value => `<option value="${value}" ${value === ts ? 'selected' : ''}>${value}</option>`).join('')}
                </select>
            </div>
            <div style="flex: 1;">
                <label for="tabEndSelect" style="display: block; margin-bottom: 5px; color: #00917B;">TabEnd</label>
                <select id="tabEndSelect" style="width: 100%; padding: 5px; border: 2px solid #00917B; border-radius: 4px; background: #42FEDC;">
                    ${allowedValues.map(value => `<option value="${value}" ${value === te ? 'selected' : ''}>${value}</option>`).join('')}
                </select>
            </div>
        </div>
    `;

    // Récupérer les éléments select
    var tabStartSelect = document.getElementById('tabStartSelect');
    var tabEndSelect = document.getElementById('tabEndSelect');

    // Mettre à jour les valeurs temporaires lors du changement des menus déroulants
    function updateTempValues() {
        tempTabStart = parseInt(tabStartSelect.value);
        tempTabEnd = parseInt(tabEndSelect.value);
        
        // Vérifier que tabStart est toujours supérieur à tabEnd
        if (tempTabStart <= tempTabEnd) {
            // Si tabStart est inférieur ou égal à tabEnd, on inverse les valeurs
            var temp = tempTabStart;
            tempTabStart = tempTabEnd;
            tempTabEnd = temp;
            
            // Mettre à jour les sélections
            tabStartSelect.value = tempTabStart;
            tabEndSelect.value = tempTabEnd;
        }
    }

    // Ajouter les écouteurs d'événements
    tabStartSelect.addEventListener('change', updateTempValues);
    tabEndSelect.addEventListener('change', updateTempValues);

    // Fonction pour appliquer les changements et recharger la page
    function applyChanges() {
        var q = parseQuery(location.search);
        q['tabStart'] = tempTabStart;
        q['tabEnd'] = tempTabEnd;
        q['zoom'] = getZoom();
        location.replace(rebuildQuery(q));
    }

    // Modifier la fonction scro pour utiliser les valeurs temporaires
    window.scro = function(v) {
        if (v === 0) { // Bouton ✓
            applyChanges();
        } else if (v === 2) { // Bouton ↺
            applyChanges();
        } else if (v === 1) { // Bouton de défilement
            var qry = parseQuery(location.search);
            qry["scroll"] = (qry["scroll"] == 0) ? 1 : 0;
            qry["zoom"] = getZoom();
            location.replace(rebuildQuery(qry));
        }
        pause = 0;
        document.getElementById('autohide').style.visibility = 'hidden';
    };

    // Mettre à jour l'URL avec les valeurs par défaut si elles ne sont pas définies
    if (!qry['tabStart'] || !qry['tabEnd']) {
        var defaultQry = parseQuery(location.search);
        defaultQry['tabStart'] = 512;
        defaultQry['tabEnd'] = 2;
        defaultQry['zoom'] = getZoom();
        location.replace(rebuildQuery(defaultQry));
    }
});

// Initialisation du slider de zoom (si présent)
var zoomSlider = document.getElementById('slider-zoom');
if (zoomSlider != null) {
    noUiSlider.create(zoomSlider, {
        start: [getZoomFromUrlOrCookie()],
        range: {
            'min': [0.01],
            'max': [4.00]
        }
    });
    zoomSlider.noUiSlider.on('update', function(values, handle) {
        var v = values[handle];
        rescale(v);
        setZoomCookie && setZoomCookie(v);
    });
}

// --- Scroll automatique haut/bas infini avec pause de 8s en haut et en bas ---
var pauseTopBottom = 0;

// Système de rafraîchissement automatique robuste
function setupAutoRefresh() {
    // Fonction principale de rafraîchissement
    function refreshPage() {
        console.log("AUTO_REFRESH_INTERVAL timeout triggered - refreshing page. Interval was:", window.auto_refresh_interval || 120000, "ms");
        try {
            // Méthode 1: Utiliser l'API History
            if (window.history && window.history.replaceState) {
                var qry = parseQuery(location.search);
                qry["zoom"] = getZoom();
                var newUrl = rebuildQuery(qry);
                window.history.replaceState({}, '', newUrl);
                window.location.reload();
            }
            // Méthode 2: Rafraîchissement standard
            else {
                window.location.reload(true);
            }
        } catch (e) {
            console.error('Erreur lors du rafraîchissement:', e);
            // Méthode 3: Rafraîchissement de secours
            try {
                window.location.href = window.location.href;
            } catch (e2) {
                console.error('Erreur lors du rafraîchissement de secours:', e2);
            }
        }
    }

    // Démarrer le rafraîchissement automatique
    var refreshInterval = setInterval(refreshPage, window.auto_refresh_interval || 120000); // Use config value or default to 2 minutes

    // Gérer la visibilité de la page
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            clearInterval(refreshInterval);
        } else {
            refreshInterval = setInterval(refreshPage, window.auto_refresh_interval || 120000);
        }
    });

    // Gérer la mise en veille de l'écran
    document.addEventListener('resume', function() {
        refreshInterval = setInterval(refreshPage, window.auto_refresh_interval || 120000);
    });

    // Rafraîchir la page quand elle redevient visible
    window.addEventListener('focus', function() {
        refreshInterval = setInterval(refreshPage, window.auto_refresh_interval || 120000);
    });

    // Arrêter le rafraîchissement quand la page perd le focus
    window.addEventListener('blur', function() {
        clearInterval(refreshInterval);    });
}

/**
 * Fullscreen functionality for the competition display
 * Compatible with desktop browsers, mobile Chrome, Android WebView, and Android TV
 */

// Variable pour tracker l'état plein écran manuel (pour les navigateurs qui ne supportent pas l'API)
var isManualFullscreen = false;

// Détection de l'environnement
function getEnvironmentInfo() {
    var userAgent = navigator.userAgent || navigator.vendor || window.opera;
    
    return {
        isAndroid: /android/i.test(userAgent),
        isAndroidTV: /android.*tv|smarttv|googletv/i.test(userAgent),
        isChrome: /chrome/i.test(userAgent) && !/edg/i.test(userAgent),
        isMobile: /android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini/i.test(userAgent),
        supportsFullscreen: !!(document.fullscreenEnabled || document.mozFullScreenEnabled || 
                              document.webkitFullscreenEnabled || document.msFullscreenEnabled)
    };
}

// Fonction principale pour entrer ou sortir du mode plein écran
function toggleFullscreen() {
    console.log('Toggle fullscreen appelé');
    var env = getEnvironmentInfo();
    
    // Vérifier si on est déjà en plein écran
    var isCurrentlyFullscreen = isInFullscreen();
    
    if (!isCurrentlyFullscreen) {
        enterFullscreen();
    } else {
        exitFullscreen();
    }
}

// Vérifier si on est en mode plein écran
function isInFullscreen() {
    return !!(document.fullscreenElement || 
              document.mozFullScreenElement || 
              document.webkitFullscreenElement || 
              document.msFullscreenElement ||
              isManualFullscreen);
}

// Fonction pour entrer en mode plein écran avec gestion multi-navigateurs
function enterFullscreen() {
    console.log('Tentative d\'entrée en plein écran');
    var elem = document.documentElement;
    var env = getEnvironmentInfo();
    
    // Méthode 1: API Fullscreen standard
    var fullscreenPromise = null;
    
    try {
        if (elem.requestFullscreen) {
            fullscreenPromise = elem.requestFullscreen();
        } else if (elem.mozRequestFullScreen) { // Firefox
            fullscreenPromise = elem.mozRequestFullScreen();
        } else if (elem.webkitRequestFullscreen) { // Chrome, Safari et Opera
            // Pour iOS Safari, essayer avec différentes options
            if (env.isMobile) {
                fullscreenPromise = elem.webkitRequestFullscreen(Element.ALLOW_KEYBOARD_INPUT);
            } else {
                fullscreenPromise = elem.webkitRequestFullscreen();
            }
        } else if (elem.msRequestFullscreen) { // IE/Edge
            fullscreenPromise = elem.msRequestFullscreen();
        }
        
        // Gérer la promesse si elle existe
        if (fullscreenPromise && typeof fullscreenPromise.then === 'function') {
            fullscreenPromise.then(function() {
                console.log('Plein écran activé avec succès');
                updateFullscreenButton();
            }).catch(function(error) {
                console.log('Erreur plein écran API:', error);
                // Fallback vers simulation manuelle
                simulateFullscreen();
            });
        } else {
            // Pour les navigateurs qui ne retournent pas de promesse
            setTimeout(function() {
                if (!isInFullscreen()) {
                    simulateFullscreen();
                } else {
                    updateFullscreenButton();
                }
            }, 100);
        }
    } catch (error) {
        console.log('Erreur lors de la demande de plein écran:', error);
        simulateFullscreen();
    }
    
    // Méthode 2: Simulation de F11 pour certains cas
    if (env.isAndroidTV || (!env.supportsFullscreen && env.isAndroid)) {
        simulateF11Key();
    }
}

// Fonction pour sortir du mode plein écran
function exitFullscreen() {
    console.log('Tentative de sortie du plein écran');
    
    // Si on est en mode manuel, revenir à la normale
    if (isManualFullscreen) {
        exitManualFullscreen();
        return;
    }
    
    try {
        if (document.exitFullscreen) {
            document.exitFullscreen().then(function() {
                updateFullscreenButton();
            }).catch(function(error) {
                console.log('Erreur sortie plein écran:', error);
                exitManualFullscreen();
            });
        } else if (document.mozCancelFullScreen) { // Firefox
            document.mozCancelFullScreen();
        } else if (document.webkitExitFullscreen) { // Chrome, Safari et Opera
            document.webkitExitFullscreen();
        } else if (document.msExitFullscreen) { // IE/Edge
            document.msExitFullscreen();
        } else {
            exitManualFullscreen();
        }
    } catch (error) {
        console.log('Erreur lors de la sortie du plein écran:', error);
        exitManualFullscreen();
    }
}

// Simulation du plein écran pour les navigateurs qui ne le supportent pas
function simulateFullscreen() {
    console.log('Simulation du plein écran activée');
    
    // Masquer les barres d'interface du navigateur
    hideUIElements();
    
    // Marquer comme plein écran manuel
    isManualFullscreen = true;
    
    // Mettre à jour le style pour occuper tout l'écran
    var body = document.body;
    var html = document.documentElement;
    
    // Sauvegarder les styles originaux
    if (!body.dataset.originalStyle) {
        body.dataset.originalStyle = body.style.cssText;
        html.dataset.originalStyle = html.style.cssText;
    }
    
    // Appliquer les styles plein écran
    body.style.cssText += `
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 100vw !important;
        height: 100vh !important;
        margin: 0 !important;
        padding: 0 !important;
        z-index: 999999 !important;
        overflow: hidden !important;
    `;
    
    html.style.cssText += `
        width: 100vw !important;
        height: 100vh !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow: hidden !important;
    `;
    
    // Masquer la barre d'adresse sur mobile
    if (window.scrollTo) {
        window.scrollTo(0, 1);
    }
    
    updateFullscreenButton();
}

// Sortir du mode plein écran manuel
function exitManualFullscreen() {
    console.log('Sortie du mode plein écran manuel');
    
    isManualFullscreen = false;
    
    var body = document.body;
    var html = document.documentElement;
    
    // Restaurer les styles originaux
    if (body.dataset.originalStyle) {
        body.style.cssText = body.dataset.originalStyle;
        delete body.dataset.originalStyle;
    }
    
    if (html.dataset.originalStyle) {
        html.style.cssText = html.dataset.originalStyle;
        delete html.dataset.originalStyle;
    }
    
    showUIElements();
    updateFullscreenButton();
}

// Masquer les éléments d'interface
function hideUIElements() {
    // filepath: c:\xampp\htdocs\your_tab_page_file.php_or_html
    // ...existing code...
  
    // Masquer la barre d'adresse et les contrôles du navigateur sur mobile
    var metaViewport = document.querySelector('meta[name=viewport]');
    if (metaViewport) {
        metaViewport.content = 'width=device-width, initial-scale=1.0, user-scalable=no, minimal-ui';
    }
    
    // Tenter de masquer la barre d'état sur Android
    if (window.StatusBar && window.StatusBar.hide) {
        window.StatusBar.hide();
    }
}

// Afficher les éléments d'interface
function showUIElements() {
    var metaViewport = document.querySelector('meta[name=viewport]');
    if (metaViewport) {
        metaViewport.content = 'width=device-width, initial-scale=1.0';
    }
    
    if (window.StatusBar && window.StatusBar.show) {
        window.StatusBar.show();
    }
}

// Simulation de la touche F11
function simulateF11Key() {
    console.log('Simulation de la touche F11');
    
    // Créer un événement clavier F11
    var f11Event = new KeyboardEvent('keydown', {
        key: 'F11',
        keyCode: 122,
        which: 122,
        bubbles: true,
        cancelable: true
    });
    
    // Envoyer l'événement
    document.dispatchEvent(f11Event);
    
    // Pour Android TV, essayer aussi d'autres méthodes
    var env = getEnvironmentInfo();
    if (env.isAndroidTV) {
        // Tenter de déclencher le plein écran via l'interface Android
        try {
            if (window.AndroidInterface && window.AndroidInterface.requestFullscreen) {
                window.AndroidInterface.requestFullscreen();
            }
        } catch (e) {
            console.log('Interface Android non disponible');
        }
        
        // Fallback: demander à l'utilisateur d'appuyer sur une touche
        setTimeout(function() {
            if (!isInFullscreen()) {
                showFullscreenInstructions();
            }
        }, 1000);
    }
}

// Afficher les instructions pour le plein écran manuel
function showFullscreenInstructions() {
    var instructions = document.createElement('div');
    instructions.id = 'fullscreen-instructions';
    instructions.innerHTML = `
        <div style="
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0, 0, 0, 0.9);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            z-index: 1000000;
            font-family: Arial, sans-serif;
        ">
            <h3>Mode Plein Écran</h3>
            <p>Pour activer le plein écran :</p>
            <ul style="text-align: left; display: inline-block;">
                <li>Appuyez sur <strong>F11</strong> (PC)</li>
                <li>Ou utilisez le menu du navigateur</li>
                <li>Sur Android TV : Menu → Plein écran</li>
            </ul>
            <button onclick="document.getElementById('fullscreen-instructions').remove();" 
                    style="margin-top: 10px; padding: 10px 20px; background: #0066cc; color: white; border: none; border-radius: 5px; cursor: pointer;">
                Fermer
            </button>
        </div>
    `;
    
    document.body.appendChild(instructions);
    
    // Supprimer automatiquement après 10 secondes
    setTimeout(function() {
        var elem = document.getElementById('fullscreen-instructions');
        if (elem) {
            elem.remove();
        }
    }, 10000);
}

// Fonction pour mettre à jour l'apparence du bouton selon l'état plein écran
function updateFullscreenButton() {
    var fullscreenButton = document.getElementById('fullscreen-button');
    if (!fullscreenButton) return;
    
    var isFullscreen = isInFullscreen();
    
    var span = fullscreenButton.querySelector('span');
    if (span) {
        span.textContent = isFullscreen ? 'Quitter plein écran' : 'Plein écran';
    }
    
    // Mettre à jour le title
    fullscreenButton.title = isFullscreen ? 'Quitter le mode plein écran (F11)' : 'Affichage plein écran (F11)';
    
    // Mettre à jour l'apparence visuelle avec les classes CSS
    if (isFullscreen) {
        fullscreenButton.classList.add('active');
    } else {
        fullscreenButton.classList.remove('active');
    }
    
    console.log('Bouton plein écran mis à jour, état:', isFullscreen ? 'actif' : 'inactif');
}

// Initialiser les événements du plein écran
function initFullscreenEvents() {
    console.log('Initialisation des événements plein écran');
    
    // Ajouter l'événement click au bouton plein écran
    var fullscreenButton = document.getElementById('fullscreen-button');
    if (fullscreenButton) {
        fullscreenButton.addEventListener('click', function(e) {
            e.preventDefault();
            toggleFullscreen();
        });
        console.log('Bouton plein écran trouvé et événement ajouté');
    } else {
        console.log('Bouton plein écran non trouvé');
    }
    
    // Écouter les changements d'état du plein écran pour mettre à jour le bouton
    document.addEventListener('fullscreenchange', updateFullscreenButton);
    document.addEventListener('mozfullscreenchange', updateFullscreenButton);
    document.addEventListener('webkitfullscreenchange', updateFullscreenButton);
    document.addEventListener('msfullscreenchange', updateFullscreenButton);
    
    // Gestion améliorée des raccourcis clavier
    document.addEventListener('keydown', function(event) {
        // F11 pour le plein écran
        if (event.key === 'F11' || event.keyCode === 122) {
            event.preventDefault();
            console.log('Touche F11 détectée');
            toggleFullscreen();
        }
        
        // Echap pour sortir du plein écran
        if (event.key === 'Escape' || event.keyCode === 27) {
            if (isInFullscreen()) {
                event.preventDefault();
                exitFullscreen();
            }
        }
    });
    
    // Détecter les changements d'orientation sur mobile
    window.addEventListener('orientationchange', function() {
        setTimeout(function() {
            if (isInFullscreen()) {
                // Réajuster après changement d'orientation
                if (isManualFullscreen) {
                    simulateFullscreen();
                }
            }
        }, 500);
    });
    
    // Mettre à jour le bouton à l'initialisation
    updateFullscreenButton();
}

// Appeler toutes les fonctions d'initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    initFullscreenEvents();
    
    // Only setup auto-refresh if autoscroll is not enabled
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('scroll') !== '1') {
        console.log("Autoscroll disabled, enabling general auto-refresh");
        setupAutoRefresh();
    } else {
        console.log("Autoscroll enabled, disabling general auto-refresh to prevent conflicts");
    }
});
