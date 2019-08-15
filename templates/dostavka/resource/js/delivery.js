$(document).ready(() => {
    $(document).on('click', '#call_spoiler_1', callSpoiler1)
    $(document).on('click', '#call_spoiler_2', callSpoiler2)
})

function callSpoiler1() {
    $('#spoiler_1').addClass('spoiler-active')
}

function callSpoiler2() {
    $('#spoiler_2').addClass('spoiler-active')
}