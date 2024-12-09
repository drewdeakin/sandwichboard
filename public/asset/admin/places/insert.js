$("#region_id").change(function() {
    var country = $('#region_id').find(":selected").val();
    $.ajax({
        url: "/api/city/?region=" + country,
        type: "GET",
        success: function(data) {
            var s = '<option selected disabled>City</option>';
            for (var i = 0; i < data.length; i++) {
                s += '<option value="' + data[i].id + '">' + data[i].name + '</option>';
            }
            $("#city_id").html(s);
        }
    });
});

$("#city_id").change(function() {
    var city = $('#city_id').find(":selected").val();
    $.ajax({
        url: "/api/suburb/?city=" + city,
        type: "GET",
        success: function(data) {
            var s = '<option selected disabled>Suburb</option>';
            for (var i = 0; i < data.length; i++) {
                s += '<option value="' + data[i].id + '">' + data[i].name + '</option>';
            }
            $("#suburb_id").html(s);
        }
    });
});