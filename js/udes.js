$(document).ready(function(){
    var $table = $('table.floatthead'),
        original_margin =  $table.outerWidth(true) - $table.outerWidth(),
        $col = $('> thead > tr > td:first-child, > thead > tr > th:first-child, > tbody > tr > td:first-child, > tbody > tr > th:first-child, > tr > td:first-child, > tr > th:first-child', $table),
        col_width = $col.outerWidth(true),
        table_left = 0;

    $col.each(function(){
        $(this).css({
            'height': $(this).outerHeight()+'px',
            'width': $(this).outerWidth()+'px'
        });
        $(this).next('td, th').css({
            'height': $(this).outerHeight()+'px',
        });
    });

    //Thead flottant
    $table.floatThead();

    //1ere colonne flottante
    $(document).on('scroll', function(){
        var sl = $(this).scrollLeft();
        if(!$table.hasClass('scrolled')) {
            var table_l = $table.offset().left;

            if (sl > table_l) {
                table_left = table_l;
                $table.addClass('scrolled');
            }
        } else {
            if(sl < table_left) {
                $table.removeClass('scrolled');
                $col.css('left', 0);
            } else {
                $col.css('left', sl+'px');
            }
        }
    });
});