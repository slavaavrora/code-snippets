+function() {
    try { var postId = document.body.className.match(/id-(\d+)/)[1]; }
    catch (ex) { return; }

    var xhr = new XMLHttpRequest(),
        blogId = bmrViewsCounter['blog_id'];

    xhr.open(
        'get',
        '/wp-content/themes/base/assets/img/postview_stats.png?blog_id=' + blogId + '&post_id=' + postId,
        true
    );
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.send();
}();