/**
 * CheckoutBridge Admin JavaScript
 * Precision Minimal — Interactive Behaviors
 * Prefix: op_cb_*
 */

jQuery(document).ready(function ($) {
  'use strict';

  // Smooth scroll positioning on page load
  window.scrollTo(0, 0);

  // Helper for safe i18n string access
  function getI18n(key, fallback) {
    if (window.op_cb_vars && op_cb_vars.i18n && op_cb_vars.i18n[key]) {
      return op_cb_vars.i18n[key];
    }
    return fallback;
  }

  /* ── 0. Instant Initialisation for jQuery Nice Select ────────────── */
  function initNiceSelect() {
    if ($.fn.niceSelect) {
      $('.op-cb-wrap select').each(function () {
        if (!$(this).hasClass('nice-select-initialized')) {
          $(this).addClass('nice-select-initialized').niceSelect();
        }
      });
    }
  }
  initNiceSelect();

  /* ── 1. Animated Toast Notification ─────────────────────────────── */
  var toastTimer = null;
  var toastExitTimer = null;

  function showToast(message, type) {
    type = type || 'success';
    if (toastTimer) clearTimeout(toastTimer);
    if (toastExitTimer) clearTimeout(toastExitTimer);
    $('.op-cb-toast').remove();

    var iconClass = 'fa-circle-check';
    var toastClass = 'op-cb-toast-success';

    if (type === 'error') {
      iconClass = 'fa-triangle-exclamation';
      toastClass = 'op-cb-toast-error';
    } else if (type === 'info') {
      iconClass = 'fa-circle-info';
      toastClass = 'op-cb-toast-info';
    }

    var $toast = $('<div>', { class: 'op-cb-toast ' + toastClass })
      .append($('<i>', { class: 'fa-solid ' + iconClass }))
      .append($('<span>').text(message));

    $('body').append($toast);

    // Trigger springy entrance animation
    setTimeout(function () {
      $toast.addClass('op-cb-toast-show');
    }, 40);

    // Trigger smooth exit animation
    toastTimer = setTimeout(function () {
      $toast.removeClass('op-cb-toast-show').addClass('op-cb-toast-hide');
      toastExitTimer = setTimeout(function () {
        $toast.remove();
      }, 300);
    }, 3400);
  }

  // Generic debounce helper to prevent excessive DOM filtering on keypress
  function debounce(func, wait) {
    var timeout;
    return function () {
      var context = this, args = arguments;
      clearTimeout(timeout);
      timeout = setTimeout(function () {
        func.apply(context, args);
      }, wait || 150);
    };
  }

  /* Auto-trigger Toast on Backend Transient Flash Message */
  if (window.op_cb_vars && op_cb_vars.flash && op_cb_vars.flash.message) {
    showToast(op_cb_vars.flash.message, op_cb_vars.flash.type || 'success');
  }

  /* ── 2. Universal Clipboard Copy (Secure In-Memory Decoding) ──── */
  $(document).on('click', '.op-cb-btn-copy, #op_cb_btn_copy_token', function (e) {
    e.preventDefault();
    var $btn = $(this);
    var text = '';

    // Safely decode in memory if base64 encoded
    if ($btn.attr('data-cb-key')) {
      try {
        text = atob($btn.attr('data-cb-key'));
      } catch (err) {
        text = '';
      }
    }

    if (!text) {
      text = $btn.attr('data-clipboard');
    }
    if (!text) return;

    var copiedMsg = getI18n('copied', 'Copied to clipboard!');

    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(function () {
        showToast(copiedMsg, 'success');
      }).catch(function () {
        var $tmp = $('<textarea style="position:fixed;opacity:0;">');
        $('body').append($tmp);
        $tmp.val(text).select();
        document.execCommand('copy');
        $tmp.remove();
        showToast(copiedMsg, 'success');
      });
    } else {
      var $tmp = $('<textarea style="position:fixed;opacity:0;">');
      $('body').append($tmp);
      $tmp.val(text).select();
      document.execCommand('copy');
      $tmp.remove();
      showToast(copiedMsg, 'success');
    }
  });

  /* ── 2.1 Token Confidential Show/Hide Toggle ────────────────────── */
  $(document).on('click', '#op_cb_btn_toggle_token', function (e) {
    e.preventDefault();
    var $input = $('#op_cb_token_display');
    var $icon  = $(this).find('i');

    if ($input.attr('type') === 'password') {
      $input.attr('type', 'text');
      $icon.removeClass('fa-eye').addClass('fa-eye-slash');
    } else {
      $input.attr('type', 'password');
      $icon.removeClass('fa-eye-slash').addClass('fa-eye');
    }
  });

  /* ── 2.2 Thank You Page URL Toggle ─────────────────────────────── */
  $(document).on('change', '#op_cb_enable_thank_you_url', function () {
    var isEnabled = $(this).is(':checked');
    var $group    = $('#op_cb_thank_you_url_group');
    var $input    = $('#op_cb_thank_you_url');

    if (isEnabled) {
      $group.slideDown(200);
      $input.prop('required', true);
    } else {
      $group.slideUp(200);
      $input.prop('required', false);
    }
  });

  /* ── 3. Code Block Copy ─────────────────────────────────────────── */
  $(document).on('click', '.op-cb-btn-copy-code', function (e) {
    e.preventDefault();
    var targetId = $(this).data('target');
    var $active;

    if (targetId && targetId.indexOf('_active') !== -1) {
      // Copy whichever tab is currently visible in this wrapper
      $active = $(this)
        .closest('.op-cb-code-wrapper')
        .find('.op-cb-code-tab-content:not(.op-cb-hidden) code');
    } else if (targetId) {
      $active = $('#' + targetId);
    } else {
      $active = $(this).closest('.op-cb-code-wrapper').find('.op-cb-code-tab-content:not(.op-cb-hidden) code');
    }

    var text = $active.length ? $active.text() : '';
    if (!text) return;

    var copiedMsg = getI18n('copied', 'Copied to clipboard!');

    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(function () {
        showToast(copiedMsg, 'success');
      }).catch(function () {
        var $tmp = $('<textarea style="position:fixed;opacity:0;">');
        $('body').append($tmp);
        $tmp.val(text).select();
        document.execCommand('copy');
        $tmp.remove();
        showToast(copiedMsg, 'success');
      });
    } else {
      var $tmp = $('<textarea style="position:fixed;opacity:0;">');
      $('body').append($tmp);
      $tmp.val(text).select();
      document.execCommand('copy');
      $tmp.remove();
      showToast(copiedMsg, 'success');
    }
  });

  /* ── 4. Documentation Code Tab Switcher ─────────────────────────── */
  function initTabs() {
    $('.op-cb-code-wrapper').each(function () {
      var $wrapper = $(this);
      var $firstBtn = $wrapper.find('.op-cb-tab-btn').first();
      if ($firstBtn.length && !$wrapper.find('.op-cb-tab-btn.is-active').length) {
        $firstBtn.addClass('is-active');
      }
      var activeTab = $wrapper.find('.op-cb-tab-btn.is-active').data('tab');
      if (activeTab) {
        $wrapper.find('.op-cb-code-tab-content').each(function () {
          if ($(this).attr('id') !== activeTab) {
            $(this).addClass('op-cb-hidden');
          }
        });
      }
    });
  }

  initTabs();

  $(document).on('click', '.op-cb-tab-btn', function (e) {
    e.preventDefault();
    var $btn     = $(this);
    var tabId    = $btn.data('tab');
    var $wrapper = $btn.closest('.op-cb-code-wrapper');

    $wrapper.find('.op-cb-tab-btn').removeClass('is-active');
    $btn.addClass('is-active');

    $wrapper.find('.op-cb-code-tab-content').addClass('op-cb-hidden');
    $wrapper.find('#' + tabId).removeClass('op-cb-hidden');
  });

  /* ── 5. Bridges List — Search & Status Filter ──────────── */
  function filterLandingsTable() {
    var q      = ($('#op_cb_table_search, #op_cb_search_landings').val() || '').toLowerCase();
    var status = ($('#op_cb_filter_status').val() || 'all');

    $('#op_cb_landings_table tbody tr').each(function () {
      var $row       = $(this);
      var rowText    = $row.text().toLowerCase();
      var rowStatus  = $row.data('status') || '';

      var matchText   = !q      || rowText.indexOf(q) !== -1;
      var matchStatus = status === 'all' || rowStatus === status;

      $row.toggle(matchText && matchStatus);
    });
  }

  $('#op_cb_table_search, #op_cb_search_landings').on('keyup input', debounce(filterLandingsTable, 150));
  $('#op_cb_filter_status').on('change', filterLandingsTable);

  /* ── 6. Product Picker — Search Filter & Counter ─────────────────── */
  function updateProductCounter() {
    var count = $('.op-cb-product-item input[type="checkbox"]:checked').length;
    var label = getI18n('selected', 'selected');
    $('#op_cb_selected_counter, #op_cb_selected_count').text(count + ' ' + label);
  }

  updateProductCounter();

  $(document).on('change', '.op-cb-product-item input[type="checkbox"]', function () {
    var $item = $(this).closest('.op-cb-product-item');
    if ($(this).is(':checked')) {
      $item.addClass('is-selected');
    } else {
      $item.removeClass('is-selected');
    }
    updateProductCounter();
  });

  $('#op_cb_product_search').on('keyup input', debounce(function () {
    var q         = $(this).val().toLowerCase();
    var total     = 0;
    var visible   = 0;

    $('.op-cb-product-item').each(function () {
      total++;
      var text = $(this).text().toLowerCase();
      if (!q || text.indexOf(q) !== -1) {
        $(this).show();
        visible++;
      } else {
        $(this).hide();
      }
    });

    var $hint = $('#op_cb_product_count_hint');
    if (q) {
      $hint.text(visible + ' / ' + total + ' shown');
    } else {
      $hint.text('');
    }

    var $grid = $('.op-cb-product-grid');
    if (visible === 0 && q) {
      if (!$grid.find('.op-cb-product-empty').length) {
        $grid.append('<span class="op-cb-product-empty">No products match your search.</span>');
      }
    } else {
      $grid.find('.op-cb-product-empty').remove();
    }
  }, 150));

  /* ── 7. Custom Confirmation Modal API (Scroll Lock + Escape Handler) ──── */
  function closeModal() {
    var $backdrop = $('#op_cb_confirm_modal');
    $backdrop.addClass('op-cb-hidden');
    $('body').removeClass('op-cb-modal-open');
    $(document).off('keyup.op_cb_modal');
  }

  function showConfirmModal(options, callback) {
    var title       = options.title || 'Confirm Action';
    var message     = options.message || 'Are you sure you want to perform this action?';
    var confirmText = options.confirmText || 'Confirm';
    var cancelText  = options.cancelText || 'Cancel';
    var isDanger    = options.danger !== false;

    var $backdrop = $('#op_cb_confirm_modal');
    if (!$backdrop.length) {
      $backdrop = $(
        '<div id="op_cb_confirm_modal" class="op-cb-modal-backdrop op-cb-hidden">' +
        '  <div class="op-cb-modal">' +
        '    <div class="op-cb-modal-header">' +
        '      <div class="op-cb-modal-icon ' + (isDanger ? 'op-cb-modal-icon-danger' : '') + '">' +
        '        <i class="fa-solid ' + (isDanger ? 'fa-triangle-exclamation' : 'fa-circle-info') + '"></i>' +
        '      </div>' +
        '      <h3 id="op_cb_modal_title">' + title + '</h3>' +
        '    </div>' +
        '    <div class="op-cb-modal-body">' +
        '      <p id="op_cb_modal_message">' + message + '</p>' +
        '    </div>' +
        '    <div class="op-cb-modal-footer">' +
        '      <button type="button" id="op_cb_modal_cancel" class="button button-secondary">' + cancelText + '</button>' +
        '      <button type="button" id="op_cb_modal_confirm" class="button ' + (isDanger ? 'button-danger' : 'button-primary') + '">' + confirmText + '</button>' +
        '    </div>' +
        '  </div>' +
        '</div>'
      );
      var $target = $('.op-cb-wrap').length ? $('.op-cb-wrap') : $('body');
      $target.append($backdrop);
    } else {
      $backdrop.find('#op_cb_modal_title').text(title);
      $backdrop.find('#op_cb_modal_message').text(message);
      $backdrop.find('#op_cb_modal_cancel').text(cancelText);
      $backdrop.find('#op_cb_modal_confirm')
        .attr('class', 'button ' + (isDanger ? 'button-danger' : 'button-primary'))
        .text(confirmText);
      $backdrop.find('.op-cb-modal-icon')
        .attr('class', 'op-cb-modal-icon ' + (isDanger ? 'op-cb-modal-icon-danger' : ''))
        .find('i').attr('class', 'fa-solid ' + (isDanger ? 'fa-triangle-exclamation' : 'fa-circle-info'));
    }

    $backdrop.removeClass('op-cb-hidden');
    $('body').addClass('op-cb-modal-open');

    $backdrop.find('#op_cb_modal_confirm, #op_cb_modal_cancel').off('click');

    $backdrop.find('#op_cb_modal_cancel').on('click', function () {
      closeModal();
      if (typeof callback === 'function') callback(false);
    });

    $backdrop.find('#op_cb_modal_confirm').on('click', function () {
      closeModal();
      if (typeof callback === 'function') callback(true);
    });

    $backdrop.off('click').on('click', function (e) {
      if ($(e.target).hasClass('op-cb-modal-backdrop')) {
        closeModal();
        if (typeof callback === 'function') callback(false);
      }
    });

    // Escape key handler
    $(document).off('keyup.op_cb_modal').on('keyup.op_cb_modal', function (e) {
      if (e.key === 'Escape' && !$backdrop.hasClass('op-cb-hidden')) {
        closeModal();
        if (typeof callback === 'function') callback(false);
      }
    });
  }

  /* Delete Campaign Confirmation via Modal */
  $(document).on('click', '.op-cb-btn-delete', function (e) {
    e.preventDefault();
    var href = $(this).attr('href');
    var confirmMsg = getI18n('confirm_delete', 'Are you sure you want to delete this bridge campaign?');
    showConfirmModal({
      title: 'Delete Campaign',
      message: confirmMsg,
      confirmText: 'Delete Campaign',
      cancelText: 'Cancel',
      danger: true
    }, function (confirmed) {
      if (confirmed && href) {
        window.location.href = href;
      }
    });
  });

  /* Revoke Token Confirmation via Modal */
  $(document).on('click', '.op-cb-btn-revoke', function (e) {
    e.preventDefault();
    var href = $(this).attr('href');
    var confirmMsg = getI18n('confirm_revoke', 'Are you sure you want to revoke and regenerate this token? Any external landing pages using the old token will immediately lose access.');
    showConfirmModal({
      title: 'Revoke Token Key',
      message: confirmMsg,
      confirmText: 'Revoke & Regenerate',
      cancelText: 'Cancel',
      danger: true
    }, function (confirmed) {
      if (confirmed && href) {
        window.location.href = href;
      }
    });
  });

  /* ── 8. REST API Diagnostic Health Test ──────────────────────────── */
  $('#op_cb_btn_test_api').on('click', function (e) {
    e.preventDefault();
    var $btn = $(this);

    $btn.prop('disabled', true).html(
      '<i class="fa-solid fa-arrows-rotate fa-spin" style="margin-right:4px;"></i> Testing…'
    );

    var restUrl = (window.op_cb_vars && op_cb_vars.rest_url) ? op_cb_vars.rest_url : '/wp-json/checkoutbridge/v1/';

    $.ajax({
      url:      restUrl + 'health',
      method:   'GET',
      dataType: 'json',
    }).done(function () {
      $btn.prop('disabled', false).html(
        '<i class="fa-solid fa-arrows-rotate" style="margin-right:4px;"></i> Run Diagnostic'
      );
      showToast('REST API endpoint (checkoutbridge/v1/health) is operational.', 'success');
    }).fail(function (xhr) {
      $btn.prop('disabled', false).html(
        '<i class="fa-solid fa-arrows-rotate" style="margin-right:4px;"></i> Run Diagnostic'
      );
      var code = xhr && xhr.status ? xhr.status : '-';
      showToast('REST API diagnostic check failed (HTTP ' + code + '). Please verify WordPress permalink settings.', 'error');
    });
  });

  /* ── 9. Quantity Package Deals Repeater ──────────────────────────── */
  var $enableQtyPricing = $('#op_cb_enable_quantity_pricing');
  var $qtyPricingBody   = $('#op_cb_quantity_pricing_body');
  var $tiersTbody       = $('#op_cb_package_tiers_tbody');
  var storeCurrency     = (window.op_cb_vars && op_cb_vars.currency_symbol) ? op_cb_vars.currency_symbol : '$';

  // Toggle card body visibility
  $enableQtyPricing.on('change', function () {
    if ($(this).is(':checked')) {
      $qtyPricingBody.slideDown(200);
      if ($tiersTbody.find('.op-cb-tier-row').length === 0) {
        addTierRow(2, '');
      }
    } else {
      $qtyPricingBody.slideUp(200);
    }
  });

  function generateUniqueTierId() {
    return 'tier_' + Math.random().toString(36).substring(2, 8);
  }

  function addTierRow(qty, price, tierId) {
    var index = $tiersTbody.find('.op-cb-tier-row').length;
    var safeQty = qty !== undefined ? qty : (index + 2);
    var safePrice = price !== undefined ? price : '';
    var safeId = tierId || generateUniqueTierId();

    var rowHtml = '<tr class="op-cb-tier-row" data-index="' + index + '">' +
      '<td>' +
        '<input type="number" name="quantity_pricing_tiers[' + index + '][quantity]" class="op-cb-input op-cb-tier-qty" min="1" max="999" value="' + safeQty + '" required>' +
      '</td>' +
      '<td>' +
        '<div class="op-cb-price-input-wrap">' +
          '<span class="op-cb-price-symbol">' + storeCurrency + '</span>' +
          '<input type="number" step="any" min="0" name="quantity_pricing_tiers[' + index + '][price]" class="op-cb-input op-cb-tier-price" value="' + safePrice + '" placeholder="0.00" required>' +
        '</div>' +
      '</td>' +
      '<td>' +
        '<div class="op-cb-tier-id-cell">' +
          '<input type="text" name="quantity_pricing_tiers[' + index + '][tier_id]" class="op-cb-input op-cb-tier-id-input" value="' + safeId + '" readonly>' +
          '<button type="button" class="op-cb-btn-icon op-cb-btn-copy-tier" data-tier-id="' + safeId + '" title="Copy Deal ID">' +
            '<i class="fa-solid fa-copy"></i>' +
          '</button>' +
        '</div>' +
      '</td>' +
      '<td style="text-align:center;">' +
        '<button type="button" class="op-cb-btn-icon op-cb-btn-delete-tier" title="Remove Deal" style="color:var(--cb-danger-600);">' +
          '<i class="fa-solid fa-trash-can"></i>' +
        '</button>' +
      '</td>' +
    '</tr>';

    $tiersTbody.append(rowHtml);
  }

  $('#op_cb_add_tier_btn').on('click', function (e) {
    e.preventDefault();
    addTierRow();
  });

  // Remove row handler
  $(document).on('click', '.op-cb-btn-delete-tier', function (e) {
    e.preventDefault();
    var $row = $(this).closest('.op-cb-tier-row');
    $row.fadeOut(150, function () {
      $row.remove();
      // Re-index remaining rows for clean form POST
      $tiersTbody.find('.op-cb-tier-row').each(function (idx) {
        $(this).attr('data-index', idx);
        $(this).find('.op-cb-tier-qty').attr('name', 'quantity_pricing_tiers[' + idx + '][quantity]');
        $(this).find('.op-cb-tier-price').attr('name', 'quantity_pricing_tiers[' + idx + '][price]');
        $(this).find('.op-cb-tier-id-input').attr('name', 'quantity_pricing_tiers[' + idx + '][tier_id]');
      });
    });
  });

  // Copy Tier ID handler
  $(document).on('click', '.op-cb-btn-copy-tier', function (e) {
    e.preventDefault();
    var tierId = $(this).attr('data-tier-id') || $(this).closest('.op-cb-tier-id-cell').find('.op-cb-tier-id-input').val();
    if (!tierId) return;

    var copiedMsg = getI18n('copied', 'Copied Deal ID: ' + tierId);
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(tierId).then(function () {
        showToast(copiedMsg, 'success');
      }).catch(function () {
        copyTierFallback(tierId, copiedMsg);
      });
    } else {
      copyTierFallback(tierId, copiedMsg);
    }
  });

  function copyTierFallback(text, msg) {
    var $tmp = $('<textarea style="position:fixed;opacity:0;">');
    $('body').append($tmp);
    $tmp.val(text).select();
    document.execCommand('copy');
    $tmp.remove();
    showToast(msg, 'success');
  }

});
