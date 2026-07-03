/**
 * Utilitaires partagés pour le canvas WP Business Model Canvas
 * Fonctions communes utilisées par public.js et dashboard.js
 */

(function() {
    'use strict';

    /**
     * Objet global pour les utilitaires du canvas
     */
    window.WP_BMC_Utils = window.WP_BMC_Utils || {};

    /**
     * Échapper du texte HTML pour éviter les injections XSS
     * @param {string} text - Texte à échapper
     * @returns {string} Texte échappé
     */
    WP_BMC_Utils.escapeHtml = function(text) {
        if (!text) return '';
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
    };

    /**
     * Extraire un message d'erreur d'une réponse AJAX
     * @param {Object} response - Réponse AJAX
     * @param {string} fallback - Message par défaut si aucune erreur trouvée
     * @returns {string} Message d'erreur
     */
    WP_BMC_Utils.extractErrorMessage = function(response, fallback) {
        fallback = fallback || 'Erreur inconnue';
        
        if (response && response.data) {
            if (typeof response.data === 'string') {
                return response.data;
            }
            if (response.data.message) {
                return response.data.message;
            }
        }
        
        return fallback;
    };

    /**
     * Obtenir l'ID du projet depuis l'URL ou les attributs data
     * @param {jQuery} $context - Contexte jQuery (optionnel)
     * @returns {string|null} ID du projet ou null
     */
    WP_BMC_Utils.getProjectId = function($context) {
        // Essayer depuis l'URL d'abord
        var urlParams = new URLSearchParams(window.location.search);
        var projectId = urlParams.get('project_id');
        
        if (projectId) {
            return projectId;
        }
        
        // Essayer depuis les attributs data
        if (typeof jQuery !== 'undefined') {
            var $ = jQuery;
            var $container = $context || $('.wp-bmc-dashboard, .wp-bmc-canvas-container');
            if ($container.length) {
                projectId = $container.data('project-id');
                if (projectId) {
                    return projectId;
                }
            }
        }
        
        return null;
    };

    /**
     * Normaliser le contenu HTML pour la comparaison
     * @param {string} str - Contenu à normaliser
     * @returns {string} Contenu normalisé
     */
    WP_BMC_Utils.normalizeContent = function(str) {
        if (!str) {
            return '';
        }
        return String(str)
            .replace(/&nbsp;/g, ' ')
            .replace(/\s+/g, ' ')
            .replace(/>\s+</g, '><')
            .trim();
    };

    /**
     * Vérifier si le contenu a changé
     * @param {string} originalContent - Contenu original
     * @param {string} newContent - Nouveau contenu
     * @returns {boolean} True si le contenu a changé
     */
    WP_BMC_Utils.hasContentChanged = function(originalContent, newContent) {
        return WP_BMC_Utils.normalizeContent(originalContent) !== WP_BMC_Utils.normalizeContent(newContent);
    };

    /**
     * Valider une adresse email
     * @param {string} email - Email à valider
     * @returns {boolean} True si l'email est valide
     */
    WP_BMC_Utils.isValidEmail = function(email) {
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    };

})();

