(function ($) {
    $(document).ready(function () {
        $('#wilokeSeenAcTokenUser').on("click", function (event) {
            event.preventDefault();
            let checkPassword = prompt("Please enter password", "");
            $.ajax({
                type: "post",
                url: WILOKE_JWT.ajaxurl,
                data: {
                    action: "wiloke-enable-seen-access-token-user",
                    password: checkPassword,
                    userID: $('#wilokeSeenAcTokenUser').attr('data-userID'),
                },
                beforeSend: function () {
                    // Có thể thực hiện công việc load hình ảnh quay quay trước khi đổ dữ liệu ra
                },
                success: function (response) {
                    if (response.success && (response.success === true)) {
                        location.reload();
                    } else {
                        let oData = response.data;
                        alert(oData.message);
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    //Làm gì đó khi có lỗi xảy ra
                    console.log('The following error occured: ' + textStatus, errorThrown);
                }
            });
        });
        $('#wilokeSeenRfTokenUser').on("click", function (event) {
            event.preventDefault();
            let checkPassword = prompt("Please enter password", "");
            $.ajax({
                type: "post",
                url: WILOKE_JWT.ajaxurl,
                data: {
                    action: "wiloke-enable-seen-refresh-token-user",
                    password: checkPassword,
                    userID: $('#wilokeSeenRfTokenUser').attr('data-userID'),
                },
                beforeSend: function () {
                    // Có thể thực hiện công việc load hình ảnh quay quay trước khi đổ dữ liệu ra
                },
                success: function (response) {
                    if (response.success && (response.success === true)) {
                        location.reload();
                    } else {
                        let oData = response.data;
                        alert(oData.msg);
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    //Làm gì đó khi có lỗi xảy ra
                    console.log('The following error occured: ' + textStatus, errorThrown);
                }
            });
        });
        $('#wilokeRenewAcToken').on("click", function (event) {
            event.preventDefault();
            let checkPassword = prompt("Please enter password", "");
            $.ajax({
                type: "post",
                url: WILOKE_JWT.ajaxurl,
                data: {
                    action: "wiloke-renew-token-user",
                    password: checkPassword,
                    userID: $('#wilokeRenewAcToken').attr('data-userID'),
                },
                beforeSend: function () {
                    // Có thể thực hiện công việc load hình ảnh quay quay trước khi đổ dữ liệu ra
                },
                success: function (response) {
                    if (response.success && (response.success === true)) {
                        location.reload();
                    } else {
                        let oData = response.data;
                        alert(oData.msg);
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
