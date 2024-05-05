<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="IE=edge,chrome=1" http-equiv="X-UA-Compatible">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Laravel Documentation</title>

    <link href="https://fonts.googleapis.com/css?family=Open+Sans&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-default.style.css") }}" media="screen">
    <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-default.print.css") }}" media="print">

    <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.10/lodash.min.js"></script>

    <link rel="stylesheet"
          href="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/styles/obsidian.min.css">
    <script src="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/highlight.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jets/0.14.1/jets.min.js"></script>

    <style id="language-style">
        /* starts out as display none and is replaced with js later  */
                    body .content .bash-example code { display: none; }
                    body .content .javascript-example code { display: none; }
            </style>

    <script>
        var tryItOutBaseUrl = "http://localhost";
        var useCsrf = Boolean();
        var csrfUrl = "/sanctum/csrf-cookie";
    </script>
    <script src="{{ asset("/vendor/scribe/js/tryitout-4.35.0.js") }}"></script>

    <script src="{{ asset("/vendor/scribe/js/theme-default-4.35.0.js") }}"></script>

</head>

<body data-languages="[&quot;bash&quot;,&quot;javascript&quot;]">

<a href="#" id="nav-button">
    <span>
        MENU
        <img src="{{ asset("/vendor/scribe/images/navbar.png") }}" alt="navbar-image"/>
    </span>
</a>
<div class="tocify-wrapper">
    
            <div class="lang-selector">
                                            <button type="button" class="lang-button" data-language-name="bash">bash</button>
                                            <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
                    </div>
    
    <div class="search">
        <input type="text" class="search" id="input-search" placeholder="Search">
    </div>

    <div id="toc">
                    <ul id="tocify-header-introduction" class="tocify-header">
                <li class="tocify-item level-1" data-unique="introduction">
                    <a href="#introduction">Introduction</a>
                </li>
                            </ul>
                    <ul id="tocify-header-authenticating-requests" class="tocify-header">
                <li class="tocify-item level-1" data-unique="authenticating-requests">
                    <a href="#authenticating-requests">Authenticating requests</a>
                </li>
                            </ul>
                    <ul id="tocify-header-endpoints" class="tocify-header">
                <li class="tocify-item level-1" data-unique="endpoints">
                    <a href="#endpoints">Endpoints</a>
                </li>
                                    <ul id="tocify-subheader-endpoints" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="endpoints-GETapi-geojson">
                                <a href="#endpoints-GETapi-geojson">Display a listing of the references in GeoJSON</a>
                            </li>
                                                                        </ul>
                            </ul>
            </div>

    <ul class="toc-footer" id="toc-footer">
                    <li style="padding-bottom: 5px;"><a href="{{ route("scribe.postman") }}">View Postman collection</a></li>
                            <li style="padding-bottom: 5px;"><a href="{{ route("scribe.openapi") }}">View OpenAPI spec</a></li>
                <li><a href="http://github.com/knuckleswtf/scribe">Documentation powered by Scribe ✍</a></li>
    </ul>

    <ul class="toc-footer" id="last-updated">
        <li>Last updated: May 5, 2024</li>
    </ul>
</div>

<div class="page-wrapper">
    <div class="dark-box"></div>
    <div class="content">
        <h1 id="introduction">Introduction</h1>
<aside>
    <strong>Base URL</strong>: <code>http://localhost</code>
</aside>
<p>This documentation aims to provide all the information you need to work with our API.</p>
<aside>As you scroll, you'll see code examples for working with the API in different programming languages in the dark area to the right (or as part of the content on mobile).
You can switch the language used with the tabs at the top right (or from the nav menu at the top left on mobile).</aside>

        <h1 id="authenticating-requests">Authenticating requests</h1>
<p>This API is not authenticated.</p>

        <h1 id="endpoints">Endpoints</h1>

    

                                <h2 id="endpoints-GETapi-geojson">Display a listing of the references in GeoJSON</h2>

<p>
</p>



<span id="example-requests-GETapi-geojson">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost/api/geojson?filter%5Breference%5D=OHFF-0001&amp;filter%5Bapproval_status%5D=received&amp;filter%5Bactivated%5D=false&amp;filter%5Bnot_activated%5D=false&amp;filter%5Bactivated_this_year%5D=true" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost/api/geojson"
);

const params = {
    "filter[reference]": "OHFF-0001",
    "filter[approval_status]": "received",
    "filter[activated]": "false",
    "filter[not_activated]": "false",
    "filter[activated_this_year]": "true",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-geojson">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;type&quot;: &quot;FeatureCollection&quot;,
    &quot;features&quot;: [
        {
            &quot;type&quot;: &quot;Feature&quot;,
            &quot;geometry&quot;: {
                &quot;type&quot;: &quot;Point&quot;,
                &quot;coordinates&quot;: [
                    24.19893,
                    61.19891
                ]
            },
            &quot;properties&quot;: {
                &quot;reference&quot;: &quot;OHFF-0665&quot;,
                &quot;is_activated&quot;: true,
                &quot;first_activation_date&quot;: &quot;1985-01-24&quot;,
                &quot;latest_activation_date&quot;: &quot;2016-01-12&quot;,
                &quot;name&quot;: &quot;Isoj&auml;rvi&quot;,
                &quot;icon&quot;: &quot;https://maps.google.com/intl/en_us/mapfiles/ms/micons/red.png&quot;,
                &quot;wdpa_id&quot;: 90633,
                &quot;karttapaikka_link&quot;: &quot;https://asiointi.maanmittauslaitos.fi/karttapaikka/?lang=fi&amp;share=customMarker&amp;n=6788167.7013611&amp;e=349481.49408152&amp;title=OHFF-0665&amp;desc=Isoj%C3%A4rvi&amp;zoom=8&quot;,
                &quot;paikkatietoikkuna_link&quot;: &quot;https://kartta.paikkatietoikkuna.fi/?zoomLevel=10&amp;coord=349481.49408152_6788167.7013611&amp;mapLayers=802+100+default,1629+100+default,1627+70+default,1628+70+default&amp;markers=2|1|ffde00|349481.49408152_6788167.7013611|OHFF-0665%20-%20Isoj%C3%A4rvi&amp;noSavedState=true&amp;showIntro=false&quot;,
                &quot;natura_2000_area&quot;: true
            }
        }
    ]
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-geojson" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-geojson"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-geojson"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-geojson" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-geojson">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-geojson" data-method="GET"
      data-path="api/geojson"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-geojson', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-geojson"
                    onclick="tryItOut('GETapi-geojson');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-geojson"
                    onclick="cancelTryOut('GETapi-geojson');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-geojson"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/geojson</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-geojson"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-geojson"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>filter[reference]</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="text" style="display: none"
                              name="filter[reference]"                data-endpoint="GETapi-geojson"
               value="OHFF-0001"
               data-component="query">
    <br>
<p>Name of the reference. Example: <code>OHFF-0001</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>filter[approval_status]</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="text" style="display: none"
                              name="filter[approval_status]"                data-endpoint="GETapi-geojson"
               value="received"
               data-component="query">
    <br>
<p>Approval status of the reference. Example: <code>received</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>received</code></li> <li><code>declined</code></li> <li><code>approved</code></li> <li><code>saved</code></li></ul>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>filter[activated]</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="text" style="display: none"
                              name="filter[activated]"                data-endpoint="GETapi-geojson"
               value="false"
               data-component="query">
    <br>
<p>Boolean for whether the reference is activated. Example: <code>false</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>true</code></li> <li><code>false</code></li></ul>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>filter[not_activated]</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="text" style="display: none"
                              name="filter[not_activated]"                data-endpoint="GETapi-geojson"
               value="false"
               data-component="query">
    <br>
<p>Boolean for whether the reference is not activated. Example: <code>false</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>true</code></li> <li><code>false</code></li></ul>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>filter[activated_this_year]</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
                <input type="text" style="display: none"
                              name="filter[activated_this_year]"                data-endpoint="GETapi-geojson"
               value="true"
               data-component="query">
    <br>
<p>Boolean for whether the reference is activated this year. Example: <code>true</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>true</code></li> <li><code>false</code></li></ul>
            </div>
                </form>

            

        
    </div>
    <div class="dark-box">
                    <div class="lang-selector">
                                                        <button type="button" class="lang-button" data-language-name="bash">bash</button>
                                                        <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
                            </div>
            </div>
</div>
</body>
</html>
