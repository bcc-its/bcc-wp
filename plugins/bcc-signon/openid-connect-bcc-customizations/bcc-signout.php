<?php
    header('Content-Security-Policy: frame-ancestors https://*.bcc.no');
?>

<!DOCTYPE html>
<html>
    <head>
        <title>signout</title>
    </head>
    <body>
        <div>
            <script src="https://widgets.bcc.no/widgets/signoutjs"></script>
            <script>
                function getQueryString(input) {
                    if (input == "") return {};

                    var result = {};

                    for (var i = 0; i < input.length; ++i) {
                        var p=input[i].split('=', 2);
                        
                        if (p.length == 1) {
                            result[p[0]] = "";
                        } else {
                            result[p[0]] = decodeURIComponent(p[1].replace(/\+/g, " "));
                        }
                    }

                    return result;
                };
                
                var qs = getQueryString(window.location.search.substr(1).split('&'))
                window.location = qs["redirectpath"];   
            </script>
        </div>
    </body>
</html>