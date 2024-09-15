== {{ $reference->name}} ==

== Perustiedot ==

{| class="wikitable"
|-
| '''Nimi''' || {{ $reference->name }}
|-
| '''Status''' || {{ ucfirst($reference->status) }}
|-
| '''Latitudi''' || {{ $reference->location->getLat() }}
|-
| '''Longitudi''' || {{ $reference->location->getLng() }}
|-
| '''Natura 2000 -alue''' || @if ($reference->natura_2000_area)
Kyllä
@else
Ei
@endif
|-
| '''Aktivoitu viimeksi''' || {{ $reference->latest_activation_date }}
|-
| '''Viimeisin aktivoija''' || {{ $reference->activators->sortByDesc('pivot.activation_date')->pluck('callsign')->first() }}
|}

== Linkit ==

[https://kartta.ohff.fi/?filter%5Breference%5D={{ $reference->reference }} kartta.ohff.fi]

[https://wwff.co/directory/?showRef={{ $reference->reference }} WWFF]

[https://www.protectedplanet.net/{{ $reference->wdpa_id }} Protected Planet]

@if ($reference->natura_2000_area && !is_null($point))
[https://kartta.paikkatietoikkuna.fi/?zoomLevel=10&coord={{ $point->getEasting() }}_{{ $point->getNorthing() }}&mapLayers=802+100+default,1629+100+default,1627+70+default,1628+70+default&markers=2|1|ffde00|{{ $point->getEasting() }}_{{ $point->getNorthing() }}|{{ $reference->reference}}%20-%20{{ urlencode($reference->name)}}&noSavedState=true&showIntro=false%22 Paikkatietoikkuna]
@elseif (!is_null($point))
[https://asiointi.maanmittauslaitos.fi/karttapaikka/?lang=fi&share=customMarker&n={{ $point->getNorthing() }}&e={{ $point->getEasting() }}&title={{ $reference->reference}}&desc={{ $reference->name }}&zoom=8%22 Kansalaisen karttapaikka]
@endif

[https://github.com/lasselehtinen/ohff-map/issues/new?template=reporting-issue-with-reference-information.md&title=Problem%20with%20{{ $reference->reference }} Raportoi virheestä kohteen tiedoissa]


== Keskustelu ja kohdevinkit ==

[[Keskustelu:{{ $reference->reference}}]]