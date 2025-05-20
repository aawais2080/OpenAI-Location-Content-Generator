jQuery(document).ready(function($){
    //Create validations on cotact form fields
    $('#custom-contact-form').on('submit', function(e) {
        e.preventDefault();
        $(this).find('input#first_name, input#last_name, input#email, input#phone, textarea#message').val('');
        alert('Form submitted successfully!');
    });
    
    
    //Locations fetch script
    fetchCountries();
    function fetchCountries() {
        $.getJSON('https://countriesnow.space/api/v0.1/countries', function(data) {
            if (data.data) {
                $.each(data.data, function(index, country) {
                    $('#country').append('<option value="'+country.country+'">'+country.country+'</option>');
                });
            }
        });
    }

    $('#country').change(function(){
        var selectedCountries = $(this).val();
        $('#state').empty();
        $('#city').empty();
    
        if (selectedCountries.length > 0) {
            selectedCountries.forEach(function(country) {
                $.ajax({
                    url: 'https://countriesnow.space/api/v0.1/countries/states',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({ country: country }),
                    success: function(response) {
                        if (response.data.states) {
                            $.each(response.data.states, function(i, state) {
                                $('#state').append(
                                    '<option value="'+state.name+'" data-country="'+country+'">'+state.name+' ('+country+')</option>'
                                );
                            });
                        }
                    }
                });
            });
        }
    });
    
    
    $('#state').change(function() {
        const selectedStates = $(this).find(':selected');
        $('#city').empty();
    
        selectedStates.each(function() {
            const state = $(this).val();
            const country = $(this).data('country');
    
            if (state && country) {
                $.ajax({
                    url: 'https://countriesnow.space/api/v0.1/countries/state/cities',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        country: country,
                        state: state
                    }),
                    success: function(response) {
                        if (response.data && Array.isArray(response.data)) {
                            response.data.forEach(function(city) {
                                $('#city').append(
                                    `<option value="${city}" data-state="${state}" data-country="${country}"> ${city} (${state}, ${country})</option>`

                                );
                            });
                        }
                    }
                });
            }
        });
    });


});


jQuery(document).ready(function($) {
    $('#generate-response').click(function (e) {
        e.preventDefault();
    
        const countries = $('#country').val();
        const states    = $('#state').val();
        const cities    = $('#city').val();
        const service   = $('#service').val();
        
        $('#openai-response textarea').val('Generating...');
        
        $.ajax({
            url: openaiLocationAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'generate_openai_response',
                countries: countries,
                states: states,
                cities: cities,
                service: service
            },
            success: function (response) {
                console.log(response);
                if (response.success) {
                    const locationContentMap = {};
        
                    response.data.forEach(entry => {
                        const match = entry.match(/^\[(City|State|Country|Service):\s*(.+?),\s*content:\s*(.+)\]$/is);
                        if (match) {
                            const type    = match[1].charAt(0).toUpperCase() + match[1].slice(1).toLowerCase(); // Normalize case
                            const name    = match[2].trim();
                            const content = match[3].trim();
                            locationContentMap[`${type}: ${name}`] = content;
                        }
                    });
        
                    let output = '';
                    for (const location in locationContentMap) {
                        output += `\n\n--- ${location} ---\n` + locationContentMap[location];
                    }
        
                    $('#openai-response textarea').val(output);
                    window.locationContentMap = locationContentMap;
                    
                    $("#openai-response #create-posts").removeAttr("disabled");
                } else {
                    $('#openai-response textarea').val('Error generating response.');
                }
            },
            error: function () {
                $('#openai-response textarea').val('Request failed. Please try again.');
            }
        });

    });
    
    
    $('#create-posts').click(function(e) {
        e.preventDefault();
        
        if (typeof window.locationContentMap === 'undefined') {
            alert('Please generate the OpenAI content first.');
            return;
        }
    
        // const locationMap = {};
        
        const locationMap = {
            countries: []
        };
    
        $('#city option:selected').each(function () {
            const city = $(this).val();
            const state = $(this).data('state');
            const country = $(this).data('country');
    
            if (!city || !state || !country) return;
    
            if (!locationMap[country]) {
                locationMap[country] = {
                    country: country,
                    states: []
                };
            }
    
            const stateObj = locationMap[country].states.find(s => s.name === state);
            if (stateObj) {
                if (!stateObj.cities.includes(city)) {
                    stateObj.cities.push(city);
                }
            } else {
                locationMap[country].states.push({
                    name: state, 
                    cities: [city]
                });
            }
        });
    
        $('#state option:selected').each(function () {
            const state = $(this).val();
            const country = $(this).data('country');
    
            if (!state || !country) return;
    
            if (!locationMap[country]) {
                locationMap[country] = {
                    country: country,
                    states: []
                };
            }
    
            const exists = locationMap[country].states.some(s => s.name === state);
            if (!exists) {
                locationMap[country].states.push({
                    name: state,
                    cities: []
                });
            }
        });
        
        
        // Step 3: Handle selected countries (with no selected states or cities)
        $('#country option:selected').each(function () {
            const country = $(this).val();
            if (!country) return;
    
            if (!locationMap[country]) {
                locationMap[country] = {
                    country: country,
                    states: []
                };
            }
        });
        
        
        const locationsArray = Object.values(locationMap);
        
        if (locationsArray.length === 0) {
            alert('Please select at least one city or state.');
            return;
        }
        // console.log(locationContentMap);
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'generate_location_posts',
                locations: locationsArray,
                city_content: window.locationContentMap || '',
                service: $('#service').val()
            },
            success: function(response) {
                // console.log(response);
                if(response.data.created && response.data.created != false){
                    console.log(response);
                    alert('Post created Successfully!');
                    $("#openai-response #create-posts").attr("disabled", "disabled");
                    $('body').append(response.data.message);
                } else {
                    alert('You can not create duplicate post with same location.');
                    $("#openai-response #create-posts").attr("disabled", "disabled");
                }
            },
            error: function(err) {
                // console.error(err.responseText);
                alert('Error generating posts. Please check the console for details.');
            }
        });
    });



});


