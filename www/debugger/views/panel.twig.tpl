<script type='text/javascript'>
    //<[CDATA[
    var _debug_cats = {{ CATS_JS|json_encode()|raw }};

    function _debug_op_close(layer){

        for (var i in _debug_cats){
            if (i == layer)
                _debug_cats[layer] = !_debug_cats[layer];
            else
                _debug_cats[i] = false;

            if (!_debug_cats[i]){
                $('#_webt_debug_container\\['+i+'\\]').css('display', 'none');
                $('#_debug_btn\\['+i+'\\]').removeClass('act');
            } else {
                $('#_webt_debug_container\\['+layer+'\\]').css('display', '');
                $('#_debug_btn\\['+i+'\\]').addClass('act');
            }
        }
    }


    var _debug_sel_item, _debug_sel_Y;
    var MouseX, MouseY;
    var _deb_mov_enabled;

    // getting client width
    var wndWidth = document.body.clientWidth;
    var wndHeight = document.body.clientHeight;

    function _debug_resize(){
        wndWidth = document.body.clientWidth;
        wndHeight = document.body.clientHeight;
        $('#_webt_debug_container').css('height', wndHeight - parseInt($('#_webt_debug_container').height()) - parseInt($('#_webt_debug_resizer').height()) - 8);

    }

    function _debug_drag(event)
    {
        if (!_deb_mov_enabled) return false;
        if (!event){
            var clientY = window.event.clientY;
        }else{
            var clientY = event.clientY;
        }

        // move invisible layer
        //$('#_debug_trnsp').css('left', _debug_sel_X + (clientX - MouseX) - 100);
        _debug_sel_item.style.top = _debug_sel_Y + (clientY - MouseY);
        return false;
    }

    function _debug_drop(event)
    {
        //webtSetCookie("_debug_spltr_y", parseInt($('#_webt_debug_resizer').css('top')), expires.toGMTString(), '/');
        document.onmousemove = null;
        document.onmouseup = null;
        _deb_mov_enabled = false;
    }


    var _deb_splitter_prepare = function(event){

        _debug_sel_item = $_('_webt_debug_resizer');
        _debug_sel_Y = parseInt(_debug_sel_item.style.pixelTop);

        // adding transparent div
        var oDiv = document.createElement('div');
        oDiv.id = '_debug_trnsp';

        oDiv.style.display = 'block';
        oDiv.style.position = 'absolute';
        oDiv.style.top = parseInt(_debug_sel_Y) - 100;
        oDiv.style.height = '200px';
        oDiv.style.width = '100%';
        oDiv.style.zindex = '10000001';

        window.document.body.insertBefore(oDiv, _debug_sel_item.nextSibling);

        MouseX = event.clientX;
        MouseY = event.clientY;

        document.onmousemove = _debug_drag;
        document.onmouseup = _debug_drop;
        _deb_mov_enabled = true;
    };

    var _deb_splitter_release = function(event){

        var ocontent = $_('_webt_debug_container');

        // remove transparent div
        var childnode = $_('_debug_trnsp');
        var removednode = window.document.body.removeChild(childnode);

        _debug_sel_item.className = '_webt_debug_resizer';
        ocontent.style.top = parseInt(_debug_sel_item.style.top);
        ocontent.style.height = parseInt(_debug_sel_item.style.top);
        //ocontent.style.left = parseInt(_debug_sel_item.style.left) + parseInt(_debug_sel_item.style.width) + 1;
        //ocontent.style.width = wndWidth - ocontent.style.left;
        _debug_resize();

    };

    var _debug_data = "<div id='_webt_debug'><div class='_dbg_menu'>" +
            "{% for k,cat in CATS %}<span id='_debug_btn[{{ k }}]' class='{{ cat.error }}' style='{% if cat.img %}background-image: url({{ cat.img }}){% endif %}' onclick='_debug_op_close(\"{{ k }}\")'>{{ k|upper }}: {{ cat.value }}{% if cat.value_detailed %} ({{ cat.value_detailed|raw }}){% endif %}</span>{% endfor %}" +
            "</div>{% for k,cat in CATS_CONTAINERS %}<div id='_webt_debug_container[{{ k }}]' class='_debug_container' style='display: none'>{{ cat|raw }}</div>{% endfor %}<div id='_webt_debug_resizer' on1mouseup='_deb_splitter_release(event)' on1mousedown='_deb_splitter_prepare(event)' >&nbsp;</div></div>";

    // adding css rules
    var cssNode = window.document.createElement('link');
    cssNode.type = 'text/css';
    cssNode.rel = 'stylesheet';
    cssNode.href = '/debugger/css/debugger.css';
    cssNode.media = 'screen';
    cssNode.title = 'dynamicLoadedSheet';
    $('head').append(cssNode);

    // append data
    document.write(_debug_data);
    $('#_webt_debug').css('width', (parseInt(window.document.body.offsetWidth) - 2) + 'px');

    //]]>
</script>
</body>