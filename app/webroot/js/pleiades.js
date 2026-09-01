$(function() {
 $(document).ajaxStart(function() {
    $('#spinner').fadeIn(50);
 });

 $(document).ajaxComplete(function() {
    $('#spinner').fadeOut(2000);
 });
});

function getColoredSpan(name, r, g, b) {
    console.log(arguments);

    r = parseFloat(r);
    g = parseFloat(g);
    b = parseFloat(b);


    var background = "#" +
        ('00' + Math.floor(r * 255).toString(16)).slice(-2) +
        ('00' + Math.floor(g * 255).toString(16)).slice(-2) + 
        ('00' + Math.floor(b * 255).toString(16)).slice(-2);

    // Choose either a light or dark border depending on the color's "luma",
    // calculated using Rec. 601 NTSC primaries. This is a weighted average
    // adjusted for the human perception of a color's lightness
    var luma =  0.30 * r + 0.59 * g + 0.11 * b;
    var color = luma > .5 ? '#222222' : '#DDDDDD';

    return '<span class="team-color" style="background: ' + background + '; border: 1px solid ' + color + ';">&nbsp;</span> ' + name;
}

function colorTeamNames() {
    var $el = $('#level-code');
    var teamPattern = /Team\s+(\w+)\s+([0-9.]+)\s+([0-9.]+)\s+([0-9.]+)/;
    var text = $el.text();
    var newText;
    var teamMatch = teamPattern.exec(text);
    var replacement;
    var teamName;
    var parts;
    var map = [];
    var k;
    var r, g, b;
    var colorCode;
    while(teamMatch) {
        console.log(teamMatch);

        teamName = teamMatch[1];
        r = teamMatch[2];
        g = teamMatch[3];
        b = teamMatch[4];
        span = getColoredSpan(teamName, r, g, b);

        replacement = ['Team',span, r, g, b].join(' ');
        map.push([teamMatch[0], replacement]);

        text = text.replace(teamMatch[0], '');
        teamMatch = teamPattern.exec(text);
    }

    newText = $el.text();
    for (k in map) {
        newText = newText.replace(map[k][0], map[k][1]);
    }
    $el.html(newText);
}

/**
 * Fetches the raw content of the submission then inserts it into the
 * submission's pre tag and fires any formatting/highlighting functions.
 *
 * Afterwards it simply toggles the code's visibility
 */
function submissionClickHandler(el) {
    var $that = $(el);
    var $pre = $that.parents('.submission-wrapper').find('.submission');

    if($pre.hasClass('loaded')) {
        $pre.toggle();
        return;
    } else {
        var url = $that.attr('href');
        $.get(url).done(function(data) {
            console.log(data);
            $pre.html(data);
            $pre.removeClass('rainbow');
            colorTeamNames();
            Rainbow.color();
            $pre.addClass('loaded');
            $pre.show();
        });
    }
}
