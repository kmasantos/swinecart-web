"use strict";

var filter = {
  apply: function() {
    // URL search syntax: ?q=value&type=value[+value]*&breed=value[+value]&sort=value*
    var types = [];
    var breeds = [];
    var breeders = [];
    var filter_parameters = "?";
    var search_query = document.getElementById("search").value;
    var type_url = "";
    var breed_url = "";
    var breeder_url = "";

    // Get all checked checkboxes regarding type of product
    $(".filter-type:checked").each(function() {
      types.push($(this).attr("data-type"));
    });

    // Get all checked checkboxes regarding breed of product
    $(".filter-breed:checked").each(function() {
      breeds.push($(this).attr("data-breed"));
    });

    // Get all checked checkboxes regarding breeders
    $(".filter-breeder:checked").each(function() {
      // manipulate the breeder name before pushed
      const breederWithRemovedSpace = $(this)
        .attr("data-breeder")
        .replace(/ /g, "-");
      breeders.push(breederWithRemovedSpace);
    });

    // Check if there is search query
    if (search_query) {
      filter_parameters += "q=" + search_query;
    }

    // Join type filter values by '+' and append to filter_parameters
    if (types.length > 0) {
      type_url = "type=";
      type_url += types.join("+");
      filter_parameters +=
        filter_parameters.length > 1 ? "&" + type_url : type_url;
    }

    // Join breed filter values by '+' and append to filter_parameters
    if (breeds.length > 0) {
      breed_url = "breed=";
      breed_url += breeds.join("+");
      filter_parameters +=
        filter_parameters.length > 1 ? "&" + breed_url : breed_url;
    }

    // Join breeder filter values by '+' and append to filter_parameters
    if (breeders.length > 0) {
      breeder_url = "breeder=";
      breeder_url += breeders.join("+");
      filter_parameters +=
        filter_parameters.length > 1 ? "&" + breeder_url : breeder_url;
    }

    // Append sort parameters to filter_parameters
    if ($("select option:selected").val() == "") {
      filter_parameters +=
        filter_parameters.length > 1 ? "&sort=none" : "sort=none";
    } else {
      filter_parameters +=
        filter_parameters.length > 1
          ? "&sort=" + $("select option:selected").val()
          : "sort=" + $("select option:selected").val();
    }

    window.location = config.viewPublicProducts + filter_parameters;
  }
};
