/**
 * FilteredGallery - JavaScript principal
 * 
 * Ce fichier contient toutes les fonctionnalités JavaScript pour le plugin FilteredGallery
 */

(function($) {
    'use strict';

    /**
     * Classe principale FilteredGallery
     */
    class FilteredGallery {
        constructor() {
            this.init();
        }

        /**
         * Initialisation du plugin
         */
        init() {
            this.bindEvents();
            this.initLightbox();
        }

        /**
         * Liaison des événements
         */
        bindEvents() {
            // Filtrage par catégorie
            $(document).on('click', '.filtered-gallery-filter', this.handleFilterClick.bind(this));
            
            // Clic sur une image pour ouvrir le carrousel
            $(document).on('click', '.filtered-gallery-item', this.handleImageClick.bind(this));
            
            // Fermeture du carrousel
            $(document).on('click', '.filtered-gallery-carousel-close', this.closeLightbox.bind(this));
            $(document).on('click', '.filtered-gallery-carousel', this.handleLightboxClick.bind(this));
            
            // Navigation dans le carrousel
            $(document).on('click', '.filtered-gallery-carousel-prev', () => this.navigateCarousel('prev'));
            $(document).on('click', '.filtered-gallery-carousel-next', () => this.navigateCarousel('next'));
            
            // Navigation clavier
            $(document).on('keydown', this.handleKeydown.bind(this));
            
            // Fermeture du carrousel avec Escape
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    this.closeLightbox();
                }
            }.bind(this));
        }

        /**
         * Gestion du clic sur un filtre
         */
        handleFilterClick(e) {
            e.preventDefault();
            
            const $filter = $(e.currentTarget);
            const $gallery = $filter.closest('.filtered-gallery');
            const category = $filter.data('category');
            
            // Mettre à jour l'état actif du filtre
            $gallery.find('.filtered-gallery-filter').removeClass('active');
            $filter.addClass('active');
            
            // Afficher l'indicateur de chargement
            const $container = $gallery.find('.filtered-gallery-container');
            $container.addClass('loading');
            
            // Effectuer la requête AJAX
            this.filterGallery($gallery, category, $container);
        }

        /**
         * Filtrage de la galerie via AJAX
         */
        filterGallery($gallery, category, $container) {
            const galleryId = $gallery.attr('id') || 'filtered-gallery-' + Math.random().toString(36).substr(2, 9);
            
            $.ajax({
                url: filtered_gallery_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'filtered_gallery_filter',
                    category: category,
                    nonce: filtered_gallery_ajax.nonce,
                    gallery_id: galleryId
                },
                success: function(response) {
                    if (response.success) {
                        this.updateGallery($container, response.data.html);
                    } else {
                        this.showError($container, filtered_gallery_ajax.strings.error);
                    }
                }.bind(this),
                error: function() {
                    this.showError($container, filtered_gallery_ajax.strings.error);
                }.bind(this),
                complete: function() {
                    $container.removeClass('loading');
                }
            });
        }

        /**
         * Mise à jour de la galerie avec le nouveau contenu
         */
        updateGallery($container, html) {
            const $grid = $container.find('.filtered-gallery-grid');
            
            if (html.trim()) {
                $grid.html(html);
                this.animateItems($grid);
            } else {
                $grid.html('<div class="filtered-gallery-no-images"><h3>Aucune image trouvée</h3><p>Aucune image n\'est disponible dans cette catégorie.</p></div>');
            }
        }

        /**
         * Animation des éléments de la galerie
         */
        animateItems($grid) {
            const $items = $grid.find('.filtered-gallery-item');
            
            $items.each(function(index) {
                const $item = $(this);
                $item.css({
                    'opacity': '0',
                    'transform': 'translateY(20px)'
                });
                
                setTimeout(function() {
                    $item.css({
                        'opacity': '1',
                        'transform': 'translateY(0)'
                    });
                }, index * 100);
            });
        }

        /**
         * Affichage d'une erreur
         */
        showError($container, message) {
            $container.html('<div class="filtered-gallery-error"><p>' + message + '</p></div>');
        }

        /**
         * Gestion du clic sur une image
         */
        handleImageClick(e) {
            e.preventDefault();
            
            const $item = $(e.currentTarget);
            const $gallery = $item.closest('.filtered-gallery');
            const $allItems = $gallery.find('.filtered-gallery-item');
            const currentIndex = $allItems.index($item);
            
            // Récupérer toutes les images de la galerie
            const images = [];
            $allItems.each(function() {
                const $img = $(this).find('img');
                const $title = $(this).find('.filtered-gallery-title');
                const $description = $(this).find('.filtered-gallery-description');
                const $category = $(this).find('.filtered-gallery-category');
                
                images.push({
                    src: $img.attr('data-full') || $img.attr('src'),
                    title: $title.text() || $img.attr('alt') || '',
                    description:  $description.text() || '',
                    category: $category.text() || ''
                });
            });
            
            this.openCarousel(images, currentIndex);
        }

        /**
         * Initialisation du carrousel
         */
        initLightbox() {
            // Créer l'élément carrousel s'il n'existe pas
            if ($('.filtered-gallery-carousel').length === 0) {
                const carouselHtml = `
                    <div class="filtered-gallery-carousel">
                        <div class="filtered-gallery-carousel-content">
                            <button class="filtered-gallery-carousel-close">&times;</button>
                            <button class="filtered-gallery-carousel-prev"></button>
                            <button class="filtered-gallery-carousel-next"></button>
                            <div class="filtered-gallery-carousel-slide">
                                <img src="" alt="">
                                <div class="filtered-gallery-carousel-info">
                                    <h3 class="filtered-gallery-carousel-title"></h3>
                                    <p class="filtered-gallery-carousel-description"></p>
                                    <p class="filtered-gallery-carousel-category"></p>
                                    <div class="filtered-gallery-carousel-counter">
                                        <span class="current">1</span> / <span class="total">1</span>
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                `;
                $('body').append(carouselHtml);
            }
        }

        /**
         * Ouverture du carrousel
         */
        openCarousel(images, startIndex = 0) {
            this.carouselImages = images;
            this.currentIndex = startIndex;
            
            const $carousel = $('.filtered-gallery-carousel');
            const $img = $carousel.find('img');
            const $title = $carousel.find('.filtered-gallery-carousel-title');
            const $description = $carousel.find('.filtered-gallery-carousel-description');
            const $category = $carousel.find('.filtered-gallery-carousel-category');
            const $counter = $carousel.find('.filtered-gallery-carousel-counter');
            const $thumbnails = $carousel.find('.filtered-gallery-carousel-thumbnails');
            
            // Mettre à jour le contenu
            this.updateCarouselSlide();
            
            // Créer les miniatures
            this.createThumbnails($thumbnails);
            
            // Afficher le carrousel
            $carousel.addClass('active');
            
            // Empêcher le défilement de la page
            $('body').addClass('filtered-gallery-carousel-open');
        }
        
        /**
         * Mise à jour de la slide actuelle
         */
        updateCarouselSlide() {
            if (!this.carouselImages || this.carouselImages.length === 0) return;
            
            const currentImage = this.carouselImages[this.currentIndex];
            const $carousel = $('.filtered-gallery-carousel');
            const $img = $carousel.find('img');
            const $title = $carousel.find('.filtered-gallery-carousel-title');
            const $description = $carousel.find('.filtered-gallery-carousel-description');
            const $category = $carousel.find('.filtered-gallery-carousel-category');
            const $counter = $carousel.find('.filtered-gallery-carousel-counter');
            const $thumbnails = $carousel.find('.filtered-gallery-carousel-thumbnails');
            
            // Mettre à jour l'image
            $img.attr('src', currentImage.src);
            $img.attr('alt', currentImage.title);
            
            // Mettre à jour les informations
            $title.text(currentImage.title);
            $description.text(currentImage.description);
            $category.text(currentImage.category === '' ? 'Aucune catégorie' : 'Catégorie : ' + currentImage.category);
            // Mettre à jour le compteur
            $counter.find('.current').text(this.currentIndex + 1);
            $counter.find('.total').text(this.carouselImages.length);
            
            // Mettre à jour les miniatures actives
            $thumbnails.find('.thumbnail').removeClass('active');
            $thumbnails.find(`.thumbnail[data-index="${this.currentIndex}"]`).addClass('active');
        }
        
        /**
         * Création des miniatures
         */
        createThumbnails($container) {
            $container.empty();
            
            this.carouselImages.forEach((image, index) => {
                const thumbnail = $(`
                    <div class="thumbnail ${index === this.currentIndex ? 'active' : ''}" data-index="${index}">
                        <img src="${image.src}" alt="${image.title}">
                    </div>
                `);
                
                thumbnail.on('click', () => {
                    this.currentIndex = index;
                    this.updateCarouselSlide();
                });
                
                $container.append(thumbnail);
            });
        }

        /**
         * Fermeture du carrousel
         */
        closeLightbox() {
            const $carousel = $('.filtered-gallery-carousel');
            $carousel.removeClass('active');
            $('body').removeClass('filtered-gallery-carousel-open');
            this.carouselImages = null;
            this.currentIndex = 0;
        }

        /**
         * Gestion du clic sur le carrousel
         */
        handleLightboxClick(e) {
            // Fermer seulement si on clique sur l'arrière-plan
            if (e.target === e.currentTarget) {
                this.closeLightbox();
            }
        }
        
        /**
         * Navigation dans le carrousel
         */
        navigateCarousel(direction) {
            if (!this.carouselImages || this.carouselImages.length === 0) return;
            
            if (direction === 'prev') {
                this.currentIndex = this.currentIndex > 0 ? this.currentIndex - 1 : this.carouselImages.length - 1;
            } else {
                this.currentIndex = this.currentIndex < this.carouselImages.length - 1 ? this.currentIndex + 1 : 0;
            }
            
            this.updateCarouselSlide();
        }

        /**
         * Gestion de la navigation clavier
         */
        handleKeydown(e) {
            const $carousel = $('.filtered-gallery-carousel');
            
            if (!$carousel.hasClass('active')) {
                return;
            }
            
            switch (e.key) {
                case 'Escape':
                    this.closeLightbox();
                    break;
                case 'ArrowLeft':
                    this.navigateCarousel('prev');
                    break;
                case 'ArrowRight':
                    this.navigateCarousel('next');
                    break;
            }
        }

        /**
         * Chargement différé des images
         */
        initLazyLoading() {
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src;
                            img.classList.remove('lazy');
                            imageObserver.unobserve(img);
                        }
                    });
                });

                document.querySelectorAll('.filtered-gallery-item img[data-src]').forEach(img => {
                    imageObserver.observe(img);
                });
            }
        }

        /**
         * Fonction utilitaire pour créer un élément de galerie
         */
        createGalleryItem(image) {
            return `
                <li class="filtered-gallery-item" data-category="${image.category_slug || ''}">
                    <img src="${image.thumbnail_url || image.image_url}" alt="${image.title}" loading="lazy">
                    <div class="filtered-gallery-category">${image.category_name || 'Non catégorisé'}</div>
                    <div class="filtered-gallery-overlay">
                        <h3 class="filtered-gallery-title">${image.title}</h3>
                        <p class="filtered-gallery-description">${image.description || ''}</p>
                        <p class="filtered-gallery-category">${image.category_name || 'Non catégorisé'}</p>
                    </div>
                </li>
            `;
        }
    }

    /**
     * Initialisation du plugin quand le DOM est prêt
     */
    $(document).ready(function() {
        // Initialiser le plugin
        window.filteredGallery = new FilteredGallery();
        
        // Initialiser le chargement différé
        if (window.filteredGallery.initLazyLoading) {
            window.filteredGallery.initLazyLoading();
        }
    });

    /**
     * Fonction globale pour créer une galerie programmatiquement
     */
    window.createFilteredGallery = function(container, options) {
        const defaultOptions = {
            columns: 3,
            spacing: 20,
            enableLightbox: true,
            enableLazyLoading: true
        };
        
        const settings = $.extend({}, defaultOptions, options);
        
        // Créer la structure HTML
        const html = `
            <div class="filtered-gallery" id="filtered-gallery-${Date.now()}">
                <div class="filtered-gallery-filters">
                    <a href="#" class="filtered-gallery-filter active" data-category="">Toutes</a>
                </div>
                <div class="filtered-gallery-container">
                    <ul class="filtered-gallery-grid columns-${settings.columns}">
                    </ul>
                </div>
            </div>
        `;
        
        $(container).html(html);
        
        // Initialiser les fonctionnalités
        if (window.filteredGallery) {
            window.filteredGallery.initLazyLoading();
        }
        
        return $(container).find('.filtered-gallery');
    };

})(jQuery); 