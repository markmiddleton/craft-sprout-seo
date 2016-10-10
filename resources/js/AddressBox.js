if (typeof Craft.SproutSeo === typeof undefined)
{
	Craft.SproutSeo = {};
}
(function($) {

	// Set all the standard Craft.SproutFields.* stuff
	$.extend(Craft.SproutSeo,
	{
		initFields: function($container) {
			$('.sproutaddressinfo-box', $container).SproutSeoField();
		}
	});

	// -------------------------------------------
	//  Custom jQuery plugins
	// -------------------------------------------

	$.extend($.fn,
	{
		SproutSeoField: function() {
			return this.each(function() {
				if (!$.data(this, 'sproutaddress-edit')) {
					new Craft.SproutSeo.AddressBox(this);
				}
			});
		},
	});


Craft.SproutSeo.AddressBox = Garnish.Base.extend({

	$addressBox: null,
	addressInfoId: null,
	$addressForm: null,
	countryCode: null,
	actionUrl: null,
	$none: null,
	modal: null,
	$editButton: null,
	init: function($addressBox, settings)
	{
		this.$addressBox = $addressBox;

		this.settings = settings;

		this.addressInfoId = this.$addressBox.data('addressinfoid');

		this._renderAddress();

		this.addListener(this.$editButton, 'click', 'editAddressBox');
	},
	_renderAddress: function()
	{
		var $buttons = $("<div class='address-buttons'/>").appendTo(this.$addressBox);

		var editLabel = '';
		if (this.addressInfoId == '')
		{
			editLabel = Craft.t("Add Address");
		}
		else
		{
			editLabel = Craft.t("Update Address");
		}


		this.$editButton = $("<a class='small btn right edit sproutaddress-edit' href=''>" + editLabel + "</a>").appendTo($buttons);

		$("<div class='address-format' />").appendTo(this.$addressBox);

		this.$none = $("<div style='display: none' />").appendTo(this.$addressBox);
		this.$addressForm = $("<div class='sproutaddress-form' />").appendTo(this.$none);

		this._updateAddressFormat();

		this.actionUrl = Craft.getActionUrl('sproutSeo/address/changeForm');
	},
	editAddressBox: function (ev) {

			ev.preventDefault();

		  var source = null;
			if (this.settings.source != null)
			{
				source = this.settings.source;
			}
			this.$target = $(ev.currentTarget);

			var countryCode = this.$addressForm.find('.sproutaddress-country-select select').val();

			this.modal = new Craft.SproutSeo.EditAddressModal(this.$addressForm, {
				onSubmit: $.proxy(this, '_saveAddress'),
				countryCode: countryCode,
				actionUrl: this.actionUrl,
				addressInfoId: this.addressInfoId,
				namespace: 'address',
				source: source
			}, this.$target);

	},
	_updateAddressFormat: function ()
	{
		var self = this;
		Craft.postActionRequest('SproutSeo/address/updateAddressFormat', { addressInfoId: this.addressInfoId }, $.proxy(function (response) {
			self.$addressBox.find('.address-format').append(response.html);
			self.$addressForm.append(response.countryCodeHtml);
			self.$addressForm.append(response.formInputHtml);
		}, this))
	},
	_saveAddress: function(data, onError)
	{
		var self = this;

		Craft.postActionRequest('SproutSeo/address/saveAddress', data, $.proxy(function (response) {
			if (response.result == true)
			{
				self.$addressBox.find('.address-format').html(response.html);
				self.$addressForm.empty();
				self.$addressForm.append(response.countryCodeHtml);
				self.$addressForm.append(response.formInputHtml);

				Craft.cp.displayNotice(Craft.t('Address Updated.'));

				this.modal.hide();
				this.modal.destroy();
			}
			else
			{
				Garnish.shake(this.modal.$form);
				onError(response.errors);
			}
		}, this))
	}
})
})(jQuery);