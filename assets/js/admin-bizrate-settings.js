jQuery(document).ready(function ($) {
  var webanalyticsidCheckbox = $("#woocommerce_bizrate_insights_bizrate_insights_webanalyticsid_enable");
  var customValue1Checkbox = $("#woocommerce_bizrate_insights_bizrate_insights_custom_value1_enable");
  var customValue2Checkbox = $("#woocommerce_bizrate_insights_bizrate_insights_custom_value2_enable");
  updateToggles();
  webanalyticsidCheckbox.change(updateToggles);
  customValue1Checkbox.change(updateToggles);
  customValue2Checkbox.change(updateToggles);

  function updateToggles() {
    var isWebanalyticsId = webanalyticsidCheckbox.is(":checked");
    toggleCheckboxRow($(".webanalyticsid-setting"), isWebanalyticsId);
    var iscustomValue1 = customValue1Checkbox.is(":checked");
    toggleCheckboxRow($(".custom_value1-setting"), iscustomValue1);
    var iscustomValue2 = customValue2Checkbox.is(":checked");
    toggleCheckboxRow($(".custom_value2-setting"), iscustomValue2);
  }

  function toggleCheckboxRow(checkbox, isVisible) {
    if (isVisible) {
      checkbox.closest("tr").show();
    } else {
      checkbox.closest("tr").hide();
    }
  }
});