var regfield_edt = false;
var tmp_ = false;
var regfields_added_ = {};
var dragFlag = false;
var dragX_ = 0;
var dragY_ = 0;
var editableRegFieldName = "";

jQuery("[name=\"newregfield_ftype\"]").live("change", function() {
	jQuery("ul.twg_new_regfields_add li, ul.twg_regfields_edit li").removeClass("twg_visible");
	jQuery("ul.twg_new_regfields_add li.twg_new_regfields_add_" + jQuery(this).val() + ", ul.twg_regfields_edit li.twg_new_regfields_add_" + jQuery(this).val()).addClass("twg_visible");
});

jQuery(".twg_set_registration_fields .twg_delete_icon").live("click", function() {
	if(!jQuery(this).hasClass("disabled")) {
		if(confirm("Are you sure you want to delete registration form field '" + jQuery.trim(jQuery("td:first", jQuery(this).parent().parent()).html()) + "'?")) {
			jQuery(this).parent().fadeOut(600, function() {
				jQuery(this).remove();
			});
		}
	}
});

var switchAddEdit = function(whichWay) {
	switch(whichWay) {
		case 'add':
			jQuery(".twg_regfields_edit").removeClass("twg_regfields_edit").addClass("twg_new_regfields_add");
			jQuery(".twg_edit_registration_fields").removeClass("twg_edit_registration_fields").addClass("twg_new_registration_fields");
			jQuery("#twg_new_regfields_adder").val('Add Field').prev().remove();
			jQuery(".twg_field_editing").removeClass("twg_field_editing");
			jQuery(".twg_edit_registration_fields h3").eq(0).text("Add new field:");
			break;
		case 'edit':
			jQuery(".twg_new_regfields_add").removeClass("twg_new_regfields_add").addClass("twg_regfields_edit");
			jQuery(".twg_new_registration_fields").removeClass("twg_new_registration_fields").addClass("twg_edit_registration_fields");
			jQuery("#twg_new_regfields_adder").val('Save');
			if(jQuery("#twg_edit_regfield_cancel").size() < 1) {
				jQuery("#twg_new_regfields_adder").before('<input type="button" id="twg_edit_regfield_cancel" value="Cancel" />');
			}
			jQuery(".twg_edit_registration_fields h3").eq(0).text("Edit selected field:");
			break;
	}
};

var setEditFieldValues = function(obj, set) {
	ftype = jQuery(".twg_reghidden_type", obj).val();
	if(typeof set === "string") {
		if(ftype != 'dropdown' || set != 'value') {
			setval = jQuery(".twg_reghidden_" + set, obj).val();
		} else {
			setval = "";
			spl = jQuery(".twg_reghidden_" + set, obj).val().split("::")[0];
			for(var i in spl) {
				jQuery(".twg_new_fieldvals").append('<div class="twg_field_val no_select">' + spl[i] + '</div>');
			}
		}
		jQuery(".twg_regfields_edit .twg_new_regfields_add_" + ftype + " .newregfield_f" + set).val(setval);
	} else {
		for(var i in set) {
			if(ftype != 'dropdown' || set[i] != 'value') {
				setval = jQuery(".twg_reghidden_" + set[i], obj).val();
			} else {
				setval = "";
				spl = jQuery(".twg_reghidden_" + set[i], obj).val().split("::");
				for(var i in spl) {
					jQuery(".twg_new_fieldvals").append('<div class="twg_field_val no_select">' + spl[i] + '</div>');
				}
			}
			jQuery(".twg_regfields_edit .twg_new_regfields_add_" + ftype + " .newregfield_f" + set[i]).val(setval);
		}
	}
};

jQuery(".twg_set_registration_fields .twg_edit_icon").live("click", function() {
	if(!jQuery(this).hasClass("disabled")) {
		switchAddEdit('edit');
		jQuery(".twg_registration_field_row").removeClass("twg_field_editing");
		jQuery(this).parent().addClass("twg_field_editing");
		ftype = jQuery(".twg_reghidden_type" , jQuery(this).parent()).val();
		jQuery(".twg_new_regfields_radiogroup #newregfield_radio_" + ftype).click().trigger("change");
		setEditFieldValues(jQuery(this).parent(), ['name', 'title', 'value', 'regex']);
		editableRegFieldName = jQuery(".twg_reghidden_name", jQuery(this).parent()).val();
	}
});

jQuery("#twg_edit_regfield_cancel").live("click", function() {
	resetAdderFields(jQuery("input[name=\"newregfield_ftype\"]:checked").val());
	switchAddEdit('add');
});

jQuery(".twg_field_val").live("click", function() {
	jQuery(".twg_field_val").removeClass("twg_active");
	jQuery(".twg_fieldval_remover").remove();
	jQuery(this).addClass("twg_active").after('<div class="twg_fieldval_remover"></div>');
});

jQuery(".twg_fieldval_remover").live("click", function() {
	jQuery(this).prev().remove();
	jQuery(this).remove();
});

jQuery(".twg_settings_updated_bar").live("click", function() {
	jQuery(this).fadeOut(1500, function() {
		jQuery(this).remove();
	});
});

var resetAdderFields = function(ftype) {
	jQuery("input[type=\"text\"]", jQuery(".twg_new_regfields_add_" + ftype)).val('');
	jQuery("select option", jQuery(".twg_new_regfields_add_" + ftype)).removeAttr("selected");
	jQuery("select option", jQuery(".twg_new_regfields_add_" + ftype)).eq(0).attr("selected", true);
	if(ftype == 'dropdown') {
		jQuery(".twg_new_fieldvals", jQuery(".twg_new_regfields_add_" + ftype)).children().remove();
	}
};

jQuery(document).ready(function() {
	jQuery(".twg_settings_tab_bar li.twg_settings_tab").bind("click", function() {
		jQuery(".twg_settings_content").removeClass("twg_visible");
		jQuery(".twg_settings_content").eq(jQuery(".twg_settings_tab_bar li.twg_settings_tab").index(jQuery(this))).addClass("twg_visible");
		jQuery(".twg_settings_tab_bar li.twg_settings_tab").removeClass("twg_active_tab");
		jQuery(".twg_settings_title").html(jQuery(this).addClass("twg_active_tab").html());
		jQuery("input[name=\"page_on_tab\"]").val(jQuery(".twg_settings_tab_bar li.twg_settings_tab").index(jQuery(this)));
		jQuery(".twg_pseudofield_dropdown").dropdownize();
	});
	
	jQuery(".twg_new_fieldval_adder").bind("click", function() {
		tmp_ = jQuery(".newregfield_fvalue", jQuery(this).parent()).val();
		if(jQuery.trim(tmp_) != '') {
			jQuery(".twg_new_fieldvals", jQuery(this).parent()).prepend('<div class="twg_field_val no_select">' + tmp_ + '</div>');
			jQuery(".newregfield_fvalue", jQuery(this).parent()).val('');
			jQuery(".newregfield_fvalue", jQuery(this).parent()).focus();
		}
	});
	
	jQuery("#twg_new_regfields_adder").bind("click", function() {
		obj = jQuery("[name=\"newregfield_ftype\"]:checked").val();
		fname = jQuery(".twg_new_regfields_add_" + obj + " .newregfield_fname").val();
		ftitle = jQuery(".twg_new_regfields_add_" + obj + " .newregfield_ftitle").val();
		fregx = jQuery(".twg_new_regfields_add_" + obj + " .newregfield_fregex").val();
		
		if(fname == '') {
			alert("Field name cannot be empty!");
			return false;
		}
		
		if(regfields_added_[fname] !== true) {
		
			if(obj == 'dropdown') {
				fval = "";
				sz = jQuery(".twg_new_fieldvals").children().size();
				jQuery(".twg_new_fieldvals").children().each(function(i) {
					fval += jQuery(this).html().replace(/^\s+/, '').replace(/\s+$/, '');
					if(i == 0) {
						vval = fval;
					}
					if(i < sz - 1) {
						fval += "::";
					}
				});
				if(sz < 1) {
					alert("Field values cannot be empty for field type 'dropdown'.");
					return false;
				}
			} else {
				fval = jQuery(".twg_new_regfields_add_" + obj + " .newregfield_fvalue").val();
				vval = fval;
			}
			
			var edited = false;
			var fcontent = '\
				<div class="twg_registration_field_row">\
					<div class="twg_regfield_proto_label">' + ftitle + '</div>\
					<div class="twg_delete_icon"></div>\
					<div class="twg_edit_icon"></div>\
					<div class="twg_registration_field twg_pseudofield_' + obj + '">' + vval + '</div>\
					<input type="hidden" name="regfields[' + fi + '][name]" value="' + fname + '" class="twg_reghidden_name" />\
					<input type="hidden" name="regfields[' + fi + '][type]" value="' + obj + '" class="twg_reghidden_type" />\
					<input type="hidden" name="regfields[' + fi + '][title]" value="' + ftitle + '" class="twg_reghidden_title" />\
					<input type="hidden" name="regfields[' + fi + '][value]" value="' + fval + '" class="twg_reghidden_value" />\
					<input type="hidden" name="regfields[' + fi + '][regex]" value="' + fregx + '" class="twg_reghidden_regex" />\
				</div>\
			';
			
			if(editableRegFieldName != "" && jQuery(".twg_field_editing").size() > 0) {
				edited = true;
				jQuery(".twg_reghidden_name[value=\"" + editableRegFieldName + "\"]").parent().after(fcontent).remove();
			} else {
				regfields_added_[fname] = true;
				jQuery(".twg_registration_fields").append(fcontent);
			}
			
			if(obj == 'dropdown') {
				jQuery(".twg_pseudofield_dropdown").dropdownize();
			}
			
			if(true === edited) {
				editableRegFieldName = "";
				switchAddEdit('add');
			}
			
			fi++;
			
			resetAdderFields(obj);
			jQuery("input[type=\"text\"]", jQuery(".twg_new_regfields_add_" + obj)).eq(0).focus();
		} else {
			alert("Field with name '" + fname + "' already exists!");
		}
	});

	jQuery("[name=\"newregfield_ftype\"]").eq(0).attr("checked", true).trigger("change");
	jQuery(".twg_settings_tab_bar li.twg_settings_tab").eq(jQuery("input[name=\"page_on_tab\"]").val()).click().trigger("click");
});

var dropdinization_started = false;

jQuery.fn.dropdownize = function() {
	var vals = false;
	jQuery(this).each(function(i) {
		if(!jQuery(this).next().hasClass("twg_dropdown_arrow")) {
			jQuery(this).after('<div class="twg_dropdown_arrow"></div>').next().after('<div class="twg_fake_dropdown_contents" id="twg_fake_dropdown_contents_' + i + '"></div>').next().hide();
			
			vals = jQuery(".twg_reghidden_value", jQuery(this).parent()).val().split("::");
			for(var dd in vals) {
				jQuery(".twg_fake_dropdown_contents", jQuery(this).parent()).append('<div class="twg_fake_dropdown_value">' + vals[dd] + '</div>');
			}
			jQuery(".twg_fake_dropdown_value").css("clear", "both");
			jQuery(this).attr("id", "twg_fake_dropdown_" + i);
		}
			
		jQuery(this).next().css({
			"position": "absolute",
			"cursor": "pointer",
			"margin-top": "1px",
			"left": jQuery(this).get(0).offsetLeft + jQuery(this).get(0).clientWidth - jQuery(this).next().get(0).clientWidth + 1
		}).next().css({
			"position": "absolute",
			"left": jQuery(this).get(0).offsetLeft,
			"top": jQuery(this).get(0).offsetTop + jQuery(this).get(0).clientHeight + 1,
			"padding": "3px 4px",
			"width": jQuery(this).get(0).clientWidth - 8,
			"height": jQuery(this).get(0).clientHeight * 4 - 6,
			"overflow-x": "hidden",
			"overflow-y": "auto",
			"z-index": "999"
		});
	});
	
	if(false === dropdinization_started) {
		jQuery(".twg_dropdown_arrow").live("click", function() {
			if(jQuery(".twg_fake_dropdown_contents", jQuery(this).parent()).is(":visible")) {
				jQuery(".twg_fake_dropdown_contents", jQuery(this).parent()).fadeOut(400);
			} else {
				jQuery(".twg_fake_dropdown_contents", jQuery(this).parent()).fadeIn(400);
			}
		});
		dropdinization_started = true;
	}
};