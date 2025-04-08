/* 

REPLACE COOKIES WITH LOCAL STORAGE
!!!! NO LONGER NEEDED, INCLUDED IN STANDARD !!!!!

*/
(function ($) {

    //extension members
    $.extend(true, jTable.prototype, {

        /************************************************************************
        * COOKIE                                                                *
        *************************************************************************/
       
        /* OVERRIDES BASE METHOD.
        /* Sets a local storage item with given key.
        *************************************************************************/
       _setCookie: function (key, value) {
            key = this._cookieKeyPrefix + key;
            localStorage.setItem(key,value);
        },

        /* OVERRIDES BASE METHOD.
        /* Gets local storage item with given key.
        *************************************************************************/
        _getCookie: function (key) {
            key = this._cookieKeyPrefix + key;
            var result = localStorage.getItem(key);
            return result;
        },

        /* OVERRIDES BASE METHOD.
        /* Remove local storage item with given key.
        *************************************************************************/
        _removeCookie: function (key) {
            key = this._cookieKeyPrefix + key;
            var result = localStorage.removeItem(key);
            return result;
        }
    });

})(jQuery);
