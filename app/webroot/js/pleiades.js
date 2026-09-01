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


    const background = "#" +
        ('00' + Math.floor(r * 255).toString(16)).slice(-2) +
        ('00' + Math.floor(g * 255).toString(16)).slice(-2) + 
        ('00' + Math.floor(b * 255).toString(16)).slice(-2);

    // Choose either a light or dark border depending on the color's "luma",
    // calculated using Rec. 601 NTSC primaries. This is a weighted average
    // adjusted for the human perception of a color's lightness
    const luma = 0.30 * r + 0.59 * g + 0.11 * b;
    const color = luma > .5 ? '#222222' : '#DDDDDD';

    return '<span class="team-color" style="background: ' + background + '; border: 1px solid ' + color + ';">&nbsp;</span> ' + name;
}

function colorTeamNames() {
    const $el = $('#level-code');
    const teamPattern = /Team\s+(\w+)\s+([0-9.]+)\s+([0-9.]+)\s+([0-9.]+)/;
    let text = $el.text();
    let teamMatch = teamPattern.exec(text);
    const map = [];
    while (teamMatch) {
        console.log(teamMatch);

        const teamName = teamMatch[1];
        const r = teamMatch[2];
        const g = teamMatch[3];
        const b = teamMatch[4];
        const span = getColoredSpan(teamName, r, g, b);

        const replacement = ['Team', span, r, g, b].join(' ');
        map.push([teamMatch[0], replacement]);

        text = text.replace(teamMatch[0], '');
        teamMatch = teamPattern.exec(text);
    }

    let newText = $el.text();
    for (const [pattern, replacement] of map) {
        newText = newText.replace(pattern, replacement);
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
    const $that = $(el);
    const $pre = $that.parents('.submission-wrapper').find('.submission');

    if ($pre.hasClass('loaded')) {
        $pre.toggle();
        return;
    } else {
        const url = $that.attr('href');
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
