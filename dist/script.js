(function ($) {
    $(document).ready(function () {
        $('#wilokeSeenAcTokenUser').click(function (event) {
            event.preventDefault();
            let checkPassword = prompt("Please enter password", "");
            $.ajax({
                type: "post",
                url: WILOKE_JWT.ajaxurl,
                data: {
                    action: "wiloke-enable-seen-access-token-user",
                    Password: checkPassword,
                    userID: $('#wilokeSeenAcTokenUser').attr('data-userID'),
                },
                beforeSend: function () {
                    // Có thể thực hiện công việc load hình ảnh quay quay trước khi đổ dữ liệu ra
                },
                success: function (response) {
                    let oResponse = JSON.parse(response);
                    if (oResponse.status && (oResponse.status === 'success')) {
                        location.reload();
                    } else {
                        alert(oResponse.msg);
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    //Làm gì đó khi có lỗi xảy ra
                    console.log('The following error occured: ' + textStatus, errorThrown);
                }
            });
        });
        $('#wilokeSeenRfTokenUser').click(function (event) {
            event.preventDefault();
            let checkPassword = prompt("Please enter password", "");
            $.ajax({
                type: "post",
                url: WILOKE_JWT.ajaxurl,
                data: {
                    action: "wiloke-enable-seen-refresh-token-user",
                    Password: checkPassword,
                    userID: $('#wilokeSeenRfTokenUser').attr('data-userID'),
                },
                beforeSend: function () {
                    // Có thể thực hiện công việc load hình ảnh quay quay trước khi đổ dữ liệu ra
                },
                success: function (response) {
                    let oResponse = JSON.parse(response);
                    if (oResponse.status && (oResponse.status === 'success')) {
                        location.reload();
                    } else {
                        alert(oResponse.msg);
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    //Làm gì đó khi có lỗi xảy ra
                    console.log('The following error occured: ' + textStatus, errorThrown);
                }
            });
        });
        $('#wilokeRenewAcToken').click(function (event) {
            event.preventDefault();
            let checkPassword = prompt("Please enter password", "");
            $.ajax({
                type: "post",
                url: WILOKE_JWT.ajaxurl,
                data: {
                    action: "wiloke-renew-token-user",
                    Password: checkPassword,
                    userId: $('#wilokeRenewAcToken').attr('data-userID'),
                },
                beforeSend: function () {
                    // Có thể thực hiện công việc load hình ảnh quay quay trước khi đổ dữ liệu ra
                },
                success: function (response) {
                    let oResponse = JSON.parse(response);
                    if (oResponse.status && (oResponse.status === 'success')) {
                        location.reload();
                    } else {
                        alert(oResponse.msg);
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    //Làm gì đó khi có lỗi xảy ra
                    console.log('The following error occured: ' + textStatus, errorThrown);
                }
            });
        });

    });
})(jQuery);