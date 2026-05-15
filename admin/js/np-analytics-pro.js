/**
 * NoticePulse — Analytics Pro JS
 *
 * Handles:
 *  - Date range tab switching
 *  - Bar filter select
 *  - AJAX data fetch (noticepulse_analytics_data)
 *  - Chart.js impressions vs clicks chart
 *  - Per-bar performance table with CTR badges and export links
 *  - Summary card totals
 *
 * Depends on: jQuery, Chart.js 4.x (loaded by PHP enqueue_assets)
 * Localised as: noticepulseAnalytics { ajaxUrl, nonce, exportUrl }
 *
 * @package NoticePulse
 * @since   2.1.0
 */
/* global jQuery, Chart, noticepulseAnalytics */

( function ( $ ) {
	'use strict';

	// ── State ─────────────────────────────────────────────────────────────────
	var currentRange  = '30d';
	var currentBarId  = 0;
	var chartInstance = null;

	// ── Cached DOM ────────────────────────────────────────────────────────────
	var $rangeTabs       = null;
	var $barSelect       = null;
	var $chartCanvas     = null;
	var $chartLoading    = null;
	var $tableLoading    = null;
	var $perfTable       = null;
	var $perfTbody       = null;
	var $tableEmpty      = null;
	var $statImpressions = null;
	var $statClicks      = null;
	var $statCtr         = null;

	// ── Init ──────────────────────────────────────────────────────────────────

	function init() {
		$rangeTabs       = $( '#np-range-tabs' );
		$barSelect       = $( '#np-bar-select' );
		$chartCanvas     = $( '#np-chart' );
		$chartLoading    = $( '#np-chart-loading' );
		$tableLoading    = $( '#np-table-loading' );
		$perfTable       = $( '#np-perf-table' );
		$perfTbody       = $( '#np-perf-tbody' );
		$tableEmpty      = $( '#np-table-empty' );
		$statImpressions = $( '#np-stat-impressions' );
		$statClicks      = $( '#np-stat-clicks' );
		$statCtr         = $( '#np-stat-ctr' );

		// Range tab clicks.
		$rangeTabs.on( 'click', '.np-an-tab', function () {
			$rangeTabs.find( '.np-an-tab' ).removeClass( 'np-an-tab--active' );
			$( this ).addClass( 'np-an-tab--active' );
			currentRange = $( this ).data( 'range' );
			fetchData();
		} );

		// Bar filter change.
		$barSelect.on( 'change', function () {
			currentBarId = parseInt( $( this ).val(), 10 ) || 0;
			fetchData();
		} );

		// Initial load.
		fetchData();
	}

	// ── AJAX Fetch ────────────────────────────────────────────────────────────

	function fetchData() {
		showLoadingState();

		$.ajax( {
			url    : noticepulseAnalytics.ajaxUrl,
			method : 'POST',
			data   : {
				action : 'noticepulse_analytics_data',
				nonce  : noticepulseAnalytics.nonce,
				range  : currentRange,
				bar_id : currentBarId,
			},
			success : function ( response ) {
				if ( response.success && response.data ) {
					renderAll( response.data );
				} else {
					showEmptyState();
				}
			},
			error : function () {
				showEmptyState();
			},
		} );
	}

	// ── Render All ────────────────────────────────────────────────────────────

	function renderAll( data ) {
		var chart      = data.chart       || [];
		var perBar     = data.per_bar     || [];
		var totalLeads = data.total_leads || 0;

		renderSummaryCards( chart, totalLeads );
		renderChart( chart );
		renderTable( perBar );
	}

	// ── Summary Cards ─────────────────────────────────────────────────────────

	function renderSummaryCards( chartData, totalLeads ) {
		var totalImpressions = 0;
		var totalClicks      = 0;

		$.each( chartData, function ( i, row ) {
			totalImpressions += row.impressions || 0;
			totalClicks      += row.clicks      || 0;
		} );

		var ctr = totalImpressions > 0
			? ( ( totalClicks / totalImpressions ) * 100 ).toFixed( 1 ) + '%'
			: '0%';

		$statImpressions.text( formatNumber( totalImpressions ) );
		$statClicks.text( formatNumber( totalClicks ) );
		$statCtr.text( ctr );
		$( '#np-stat-leads' ).text( formatNumber( totalLeads || 0 ) );
	}

	// ── Chart ─────────────────────────────────────────────────────────────────

	function renderChart( chartData ) {
		$chartLoading.hide();
		$chartCanvas.show();

		var labels      = [];
		var impressions = [];
		var clicks      = [];

		$.each( chartData, function ( i, row ) {
			labels.push( formatDateLabel( row.date ) );
			impressions.push( row.impressions || 0 );
			clicks.push( row.clicks      || 0 );
		} );

		if ( chartInstance ) {
			chartInstance.destroy();
			chartInstance = null;
		}

		var ctx = $chartCanvas[ 0 ].getContext( '2d' );

		chartInstance = new Chart( ctx, {
			type : 'line',
			data : {
				labels   : labels,
				datasets : [
					{
						label           : 'Impressions',
						data            : impressions,
						borderColor     : '#7c5cfc',
						backgroundColor : 'rgba(124,92,252,0.12)',
						borderWidth     : 2,
						pointRadius     : chartData.length > 30 ? 0 : 3,
						pointHoverRadius: 5,
						tension         : 0.35,
						fill            : true,
					},
					{
						label           : 'Clicks',
						data            : clicks,
						borderColor     : '#22d3a5',
						backgroundColor : 'rgba(34,211,165,0.10)',
						borderWidth     : 2,
						pointRadius     : chartData.length > 30 ? 0 : 3,
						pointHoverRadius: 5,
						tension         : 0.35,
						fill            : true,
					},
				],
			},
			options : {
				responsive         : true,
				maintainAspectRatio: false,
				interaction        : { mode: 'index', intersect: false },
				plugins            : {
					legend  : { display: false },
					tooltip : {
						backgroundColor : '#1a1a2e',
						borderColor     : '#2a2a3e',
						borderWidth     : 1,
						titleColor      : '#c8c8e8',
						bodyColor       : '#888',
						padding         : 12,
						callbacks       : {
							label : function ( ctx ) {
								return ' ' + ctx.dataset.label + ': ' + formatNumber( ctx.parsed.y );
							},
						},
					},
				},
				scales : {
					x : {
						grid  : { color: 'rgba(255,255,255,0.04)', drawBorder: false },
						ticks : {
							color    : '#555',
							maxTicksLimit: 10,
							font     : { size: 11 },
						},
					},
					y : {
						beginAtZero: true,
						grid  : { color: 'rgba(255,255,255,0.04)', drawBorder: false },
						ticks : {
							color    : '#555',
							font     : { size: 11 },
							callback : function ( val ) {
								return formatNumber( val );
							},
						},
					},
				},
			},
		} );

		// Fix canvas height after Chart.js renders.
		$chartCanvas[ 0 ].parentElement.style.minHeight = '';
	}

	// ── Per-bar Table ─────────────────────────────────────────────────────────

	function renderTable( perBar ) {
		$tableLoading.hide();

		if ( ! perBar || perBar.length === 0 ) {
			$perfTable.hide();
			$tableEmpty.show();
			return;
		}

		$tableEmpty.hide();
		$perfTbody.empty();

		$.each( perBar, function ( i, row ) {
			var ctrClass = row.ctr >= 5 ? 'np-ctr-badge--high'
			             : row.ctr >= 2 ? 'np-ctr-badge--medium'
			             : 'np-ctr-badge--low';

			var exportUrl = noticepulseAnalytics.ajaxUrl
				+ '?action=noticepulse_export_leads&nonce=' + encodeURIComponent( noticepulseAnalytics.nonce )
				+ '&bar_id=' + encodeURIComponent( row.bar_id );

			var tr = '<tr>'
				+ '<td class="np-bar-name">' + escHtml( row.name ) + '</td>'
				+ '<td class="np-col-num">' + formatNumber( row.impressions ) + '</td>'
				+ '<td class="np-col-num">' + formatNumber( row.clicks ) + '</td>'
				+ '<td class="np-col-num"><span class="np-ctr-badge ' + ctrClass + '">' + row.ctr + '%</span></td>'
				+ '<td class="np-col-num">' + formatNumber( row.leads || 0 ) + '</td>'
				+ '<td><a class="np-export-link" href="' + escHtml( exportUrl ) + '">⬇ Leads CSV</a></td>'
				+ '</tr>';

			$perfTbody.append( tr );
		} );

		$perfTable.show();
	}

	// ── Loading / Empty states ────────────────────────────────────────────────

	function showLoadingState() {
		$chartLoading.show();
		$chartCanvas.hide();
		$tableLoading.show();
		$perfTable.hide();
		$tableEmpty.hide();
		$statImpressions.text( '—' );
		$statClicks.text( '—' );
		$statCtr.text( '—' );
	}

	function showEmptyState() {
		$chartLoading.hide();
		$tableLoading.hide();
		$tableEmpty.show();
	}

	// ── Helpers ───────────────────────────────────────────────────────────────

	function formatNumber( n ) {
		n = parseInt( n, 10 ) || 0;
		if ( n >= 1000000 ) { return ( n / 1000000 ).toFixed( 1 ) + 'M'; }
		if ( n >= 1000    ) { return ( n / 1000    ).toFixed( 1 ) + 'k'; }
		return n.toString();
	}

	function formatDateLabel( dateStr ) {
		if ( ! dateStr ) { return ''; }
		var parts = dateStr.split( '-' );
		if ( parts.length < 3 ) { return dateStr; }
		var months = [ 'Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec' ];
		return months[ parseInt( parts[ 1 ], 10 ) - 1 ] + ' ' + parseInt( parts[ 2 ], 10 );
	}

	function escHtml( str ) {
		return String( str )
			.replace( /&/g,  '&amp;'  )
			.replace( /</g,  '&lt;'   )
			.replace( />/g,  '&gt;'   )
			.replace( /"/g,  '&quot;' )
			.replace( /'/g,  '&#039;' );
	}

	// ── Boot ──────────────────────────────────────────────────────────────────
	$( document ).ready( init );

}( jQuery ) );
