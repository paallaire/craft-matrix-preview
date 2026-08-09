/**
 * MatrixPreview
 * -------------
 * Ajoute un bouton "Show image preview" à côté du bouton natif "New entry"
 * d'un champ Matrix, et un modal pour choisir rapidement un entry type.
 *
 * IMPORTANT : les sélecteurs CSS ci-dessous (findNewEntryButton, findMenuItems)
 * dépendent du HTML généré par Craft. Ils fonctionnent avec les structures
 * les plus courantes, mais si ton champ utilise un mode d'affichage
 * particulier (Index, Cards, groupes de boutons, etc.), ouvre les devtools,
 * inspecte le bouton "New entry" réel, et ajuste les sélecteurs en conséquence.
 */
(function () {
  if (typeof Craft === 'undefined') {
    return;
  }

  Craft.MatrixPreview = Garnish.Base.extend({
    containerId: null,
    entryTypes: [],
    $container: null,
    $newEntryBtn: null,
    $previewBtn: null,

    init: function (settings) {
      this.containerId = settings.containerId;
      this.entryTypes = settings.entryTypes;

      this.$container = $('#' + this.containerId);
      if (!this.$container.length) {
        console.warn('MatrixPreview: conteneur introuvable pour', this.containerId);
        return;
      }

      this.$newEntryBtn = this.findNewEntryButton();
      if (!this.$newEntryBtn || !this.$newEntryBtn.length) {
        console.warn('MatrixPreview: bouton "New entry" introuvable. Vérifie le sélecteur dans findNewEntryButton().');
        return;
      }

      this.addPreviewButton();
    },

    /**
     * Cherche le bouton natif "New entry" dans le conteneur du champ.
     * On cherche par texte car le libellé peut être personnalisé côté
     * réglages du champ ("New entry" par défaut), et le texte reste le
     * moyen le plus fiable de le retrouver peu importe la vue (Blocks/Cards).
     */
    findNewEntryButton: function () {
      var self = this;
      var $buttons = this.$container.find('button, .btn').filter(function () {
        var text = $(this).text().trim().toLowerCase();
        return text.indexOf('new') === 0 || text.indexOf('nouvel') === 0;
      });
      return $buttons.last();
    },

    /**
     * Insère notre bouton juste après le bouton natif.
     */
    addPreviewButton: function () {
      this.$previewBtn = $('<button/>', {
        type: 'button',
        class: 'btn',
        text: Craft.t('app', 'Show image preview')
      }).insertAfter(this.$newEntryBtn);

      this.addListener(this.$previewBtn, 'click', 'openModal');
    },

    /**
     * Ouvre le modal listant les entry types disponibles.
     */
    openModal: function () {
      var self = this;

      var $modal = $('<div/>', { class: 'modal mp-modal' });
      var $body = $('<div/>', { class: 'body mp-modal-body' }).appendTo($modal);

      var $header = $('<div/>', { class: 'mp-modal-header' }).appendTo($body);
      $('<h2/>').text(Craft.t('app', 'Choose an entry type')).appendTo($header);

      var $closeBtn = $('<button/>', {
        type: 'div',
        class: 'mp-modal-close',
        'aria-label': Craft.t('app', 'Close'),
        html: 'Close'
      }).appendTo($header);

      $closeBtn.on('click', function () {
        modalInstance.hide();
      });

      var $list = $('<div/>', { class: 'mp-entry-type-list' }).appendTo($body);

      this.entryTypes.forEach(function (entryType) {
        var $btn = $('<button/>', {
          type: 'button',
          class: 'btn mp-entry-type-btn'
        }).appendTo($list);

        // Image de prévisualisation (@web/<champ>/<entry type>.jpg).
        // Si le fichier n'existe pas (404), on masque simplement l'image
        // au lieu de laisser une icône brisée.
        if (entryType.imageUrl) {
          var $img = $('<img/>', {
            class: 'mp-entry-type-img',
            src: entryType.imageUrl,
            alt: entryType.name
          }).appendTo($btn);

          // $img.on('error', function () {
          //     $(this).remove();
          // });
        }

        $('<span/>', { class: 'mp-entry-type-label', text: entryType.name }).appendTo($btn);

        $btn.on('click', function () {
          modalInstance.hide();
          self.addEntry(entryType);
        });
      });

      $('body').append($modal);

      // Garnish.Modal gère l'affichage/fermeture (overlay, esc, clic dehors...)
      var modalInstance = new Garnish.Modal($modal, {
        onHide: function () {
          $modal.remove();
        }
      });

      modalInstance.$container.css({
        overflow: 'auto'
      });
    },

    /**
     * Ajoute une entrée du type choisi, en réutilisant le comportement
     * natif de Craft : on clique sur "New entry", puis, si un menu de
     * choix apparaît (cas où il y a plusieurs entry types), on clique
     * automatiquement sur celui qui correspond. Craft ajoute toujours
     * le nouveau bloc à la fin de la liste.
     */
    addEntry: function (entryType) {
      var self = this;

      this.$newEntryBtn.trigger('click');

      // Le menu de choix (s'il y en a un) est ajouté au DOM juste après
      // le clic. On l'observe brièvement pour y cliquer automatiquement.
      var attempts = 0;
      var maxAttempts = 20; // ~2 secondes (20 x 100ms)

      var interval = setInterval(function () {
        attempts++;

        var $menuItems = self.findMenuItems();

        if ($menuItems.length) {
          clearInterval(interval);
          $menuItems.each(function () {
            var text = $(this).text().trim();
            if (text === entryType.name) {
              $(this).trigger('click');
            }
          });
        }

        if (attempts >= maxAttempts) {
          clearInterval(interval);
        }
      }, 100);
    },

    /**
     * Cherche les items du menu de sélection d'entry type qui apparaît
     * quand on clique sur "New entry" et qu'il y a plusieurs types possibles.
     */
    findMenuItems: function () {
      return $('.menu:visible a, .menu:visible button').filter(function () {
        return $(this).text().trim().length > 0;
      });
    }
  });
})();
