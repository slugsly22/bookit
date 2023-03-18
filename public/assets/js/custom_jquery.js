function leadsearch() {
  var country,
    state,
    zipcode,
    city,
    filterState,
    filterCountry,
    filterZipcode,
    min,
    max,
    filterCity,
    rows,
    i,
    country = document.getElementById('country');
  state = document.getElementById('state');
  city = document.getElementById('city');
  zipcode = document.getElementById('zipcode');
  min = 0;
  max = 0;
  if (document.getElementById('min_price')) {
    min = document.getElementById('min_price').value;
    max = document.getElementById('max_price').value;
  }

  filterCountry = country.value.toUpperCase();
  filterState = state.value.toUpperCase();
  filterCity = city.value.toUpperCase();
  filterZipcode = zipcode.value.toUpperCase();

  rows = document.querySelector('#leadstbl tbody').rows;
  for (i = 0; i < rows.length; i++) {
    var tdCountry = rows[i].cells[7].textContent.toUpperCase();
    var tdState = rows[i].cells[8].textContent.toUpperCase();
    var tdCity = rows[i].cells[9].textContent.toUpperCase();
    var tdzipcode = rows[i].cells[10].textContent.toUpperCase();
    var price = parseInt(rows[i].cells[12].textContent.toUpperCase());
    //alert(price);
    if (
      tdCountry.indexOf(filterCountry) > -1 &&
      tdState.indexOf(filterState) > -1 &&
      tdCity.indexOf(filterCity) > -1 &&
      tdzipcode.indexOf(filterZipcode) > -1 &&
      ((isNaN(min) && isNaN(max)) ||
        (isNaN(min) && price <= max) ||
        (min <= price && isNaN(max)) ||
        (min <= price && price <= max))
    ) {
      rows[i].style.display = '';
    } else {
      rows[i].style.display = 'none';
    }
  }
}
jQuery(document).ready(function ($) {
  $('#leadstbl').DataTable({
	  language: {
				  paginate: {
					  next: '<span class="btn_next">></span>',
					  previous: '<span class="btn_prev"><</span>'  
				  }
			  },
    columnDefs: [
      {
        targets: ['_all'],
        className: 'mdc-data-table__cell',
      },
      {
        orderable: false,
        targets: [3],
      },
    ],
    searchPlaceholder: 'Search',
  });

  $(document).on('click', '.leadaddtocart', function (event) {
    var id = $(this).attr('data-id');
    var $thisbutton = $(this);
    $.ajax({
      type: 'post',
      url: ajax_script.ajaxurl,
      data: { action: 'lead_add_to_cart', id: id },
      beforeSend: function (response) {
        $thisbutton.removeClass('added').addClass('loading');
      },
      complete: function (response) {
        $thisbutton.addClass('added').removeClass('loading');
      },
      success: function (response) {
        if (response) {
          alert(response);
        } else {
          $thisbutton
            .parent()
            .html(
              '<a class="added buyleadbtn" href="javascript:void(0)" >Added</a> <a class="remove_cart buyleadbtn" href="javascript:void(0)" data-id="' +
                id +
                '">Remove</a>'
            );
        }
      },
    });
    event.preventDefault();
  });
  $(document).on('click', '.remove_cart', function (event) {
    var id = $(this).attr('data-id');
    var $thisbutton = $(this);
    $.ajax({
      type: 'post',
      url: ajax_script.ajaxurl,
      data: { action: 'lead_remove_cart', id: id },
      beforeSend: function (response) {
        $thisbutton.addClass('loading');
      },
      complete: function (response) {
        $thisbutton.removeClass('loading');
      },
      success: function (response) {
        $thisbutton
          .parent()
          .html(
            '<a class="leadaddtocart buyleadbtn" href="javascript:void(0)" data-id="' +
              id +
              '">Add to Cart</a> '
          );
      },
    });
    event.preventDefault();
  });

  $('.directbuy').click(function (event) {
    var id = $(this).attr('data-id');
    var $thisbutton = $(this);
    $.ajax({
      type: 'post',
      url: ajax_script.ajaxurl,
      data: { action: 'directleadtobuy', id: id },
      success: function (response) {
        window.location.href = ajax_script.redirecturl;
      },
    });
    event.preventDefault();
  });
});

function leadsearch_attribute() {
  var country,
    state,
    zipcode,
    city,
    filterState,
    filterCountry,
    filterZipcode,
    min,
    max,
    filterCity,
    rows,
    i,
    country = document.getElementById('country');
  state = document.getElementById('state');
  city = document.getElementById('city');
  zipcode = document.getElementById('zipcode');
  min = 0;
  max = 0;
  if (document.getElementById('attribute_min_price')) {
    min = document.getElementById('attribute_min_price').value;
    max = document.getElementById('attribute_max_price').value;
  }

  filterCountry = country.value.toUpperCase();
  filterState = state.value.toUpperCase();
  filterCity = city.value.toUpperCase();
  filterZipcode = zipcode.value.toUpperCase();

  rows = document.querySelector('#leadstbl tbody').rows;
  for (i = 0; i < rows.length; i++) {
    var tdCountry = rows[i].cells[6].textContent.toUpperCase();
    var tdState = rows[i].cells[7].textContent.toUpperCase();
    var tdCity = rows[i].cells[8].textContent.toUpperCase();
    var tdzipcode = rows[i].cells[9].textContent.toUpperCase();
    var price = parseInt(rows[i].cells[11].textContent.toUpperCase());
    //alert(price);
    if (
      tdCountry.indexOf(filterCountry) > -1 &&
      tdState.indexOf(filterState) > -1 &&
      tdCity.indexOf(filterCity) > -1 &&
      tdzipcode.indexOf(filterZipcode) > -1 &&
      ((isNaN(min) && isNaN(max)) ||
        (isNaN(min) && price <= max) ||
        (min <= price && isNaN(max)) ||
        (min <= price && price <= max))
    ) {
      rows[i].style.display = '';
    } else {
      rows[i].style.display = 'none';
    }
  }
}

var lead_state = [];
if (document.getElementById('country')) {
  document.getElementById('country').addEventListener('change', (event) => {
    lead_state = [];
    var country_id = event.target.value;
    if (states.length > 0) {
      for (var i = 0; i < states.length; i++) {
        if (states[i].country_code == country_id) {
          lead_state.push(states[i].name);
        } else {
        }
      }
    }
    autocomplete(document.getElementById('state'), lead_state);
  });
}
