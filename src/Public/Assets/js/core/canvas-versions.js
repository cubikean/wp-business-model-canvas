/**
 * Gestion des versions du canvas (optimistic locking)
 * Système de contrôle de version pour éviter les conflits de sauvegarde
 */

(function() {
    'use strict';

    /**
     * Objet global pour la gestion des versions du canvas
     */
    window.WP_BMC_CanvasVersions = window.WP_BMC_CanvasVersions || {};

    // Initialiser les versions depuis la variable globale WordPress si disponible
    WP_BMC_CanvasVersions.versions = window.wp_bmc_canvas_versions || {};

    /**
     * Fusionner de nouvelles versions avec les versions existantes
     * @param {Object} newVersions - Nouvelles versions à fusionner
     */
    WP_BMC_CanvasVersions.merge = function(newVersions) {
        if (!newVersions || typeof newVersions !== 'object') {
            return;
        }
        
        Object.keys(newVersions).forEach(function(sectionKey) {
            WP_BMC_CanvasVersions.versions[sectionKey] = newVersions[sectionKey];
        });
    };

    /**
     * Obtenir le payload des versions pour l'envoi AJAX
     * @returns {Object} Objet contenant toutes les versions
     */
    WP_BMC_CanvasVersions.getPayload = function() {
        return WP_BMC_CanvasVersions.versions || {};
    };

    /**
     * Réinitialiser les versions
     */
    WP_BMC_CanvasVersions.reset = function() {
        WP_BMC_CanvasVersions.versions = {};
    };

    /**
     * Obtenir la version d'une section spécifique
     * @param {string} sectionKey - Clé de la section
     * @returns {string|null} Version de la section ou null
     */
    WP_BMC_CanvasVersions.getSectionVersion = function(sectionKey) {
        return WP_BMC_CanvasVersions.versions[sectionKey] || null;
    };

    /**
     * Définir la version d'une section spécifique
     * @param {string} sectionKey - Clé de la section
     * @param {string} version - Version à définir
     */
    WP_BMC_CanvasVersions.setSectionVersion = function(sectionKey, version) {
        WP_BMC_CanvasVersions.versions[sectionKey] = version;
    };

})();

