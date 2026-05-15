@if (auth()->user()->fhrd == 1)
<div class="tab-pane" id="calendarView" style="margin-bottom: 25px;">
    <div class="card shadow-sm">
        <div class="card-body">
            <div id="contractCalendar" style="min-height:550px;"></div>
        </div>
    </div>
</div>
@endif

<style>
    .fc {
        font-family: Roboto, Arial, sans-serif;
        font-size: 13px;
        color: #202124;
    }

    .fc-col-header-cell {
        background: #B63352;
        text-align: center;
        padding: 10px 0;
    }

    .fc-col-header-cell-cushion {
        display: block;
        width: 100%;
        text-align: center;
        font-weight: 600;
        color: #ffffff;
        text-decoration: none !important;
    }

    .fc a {
        text-decoration: none !important;
        text-align: center;
    }

    .fc-toolbar-title {
        font-size: 20px;
        font-weight: 600;
        color: #202124;
    }

    .fc-daygrid-day-top {
        display: flex;
        justify-content: center !important;
        /* ⬅️ override bawaan */
    }

    /* FRAME ISI TANGGAL */
    .fc-daygrid-day-frame {
        display: flex;
        flex-direction: column;
        /* ⬅️ penting */
        align-items: stretch;
        justify-content: flex-start;
        padding-top: 6px;
    }

    .fc-daygrid-day-number {
        align-self: center;
        /* ⬅️ center horizontal */
        margin-bottom: 4px;
        /* jarak ke event */
        font-size: 13px;
        font-weight: 500;
        color: #3c4043;
    }


    .fc-daygrid-day-number a {
        display: block;
        width: 100%;
        text-decoration: none !important;
        color: inherit;
        text-align: center;
    }

    .fc-button {
        background: #ffffff;
        border: 1px solid #dadce0;
        color: #3c4043;
        border-radius: 6px;
        padding: 4px 10px;
        box-shadow: none;
    }

    .fc-button:hover {
        background: #f1f3f4;
    }

    .fc-button-primary:not(:disabled):active,
    .fc-button-active {
        background: #e8f0fe;
        border-color: #1a73e8;
        color: #1a73e8;
    }

    .fc-theme-standard th,
    .fc-theme-standard td {
        border: 1px solid #e0e0e0;
    }

    .fc-scrollgrid {
        border-radius: 8px;
        overflow: hidden;
    }

    .fc-daygrid-day {
        transition: background 0.15s ease;
    }

    .fc-daygrid-day:hover {
        background: #f1f3f4;
        cursor: pointer;
    }

    .fc-day-today {
        background: transparent !important;
    }

    .fc-day-today .fc-daygrid-day-number {
        background: #1a73e8;
        color: #ffffff;
        border-radius: 50%;
        width: 28px;
        height: 28px;
        line-height: 28px;
        text-align: center;
        display: inline-block;
    }

    .fc-daygrid-event {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
    }

    .fc-daygrid-event-harness {
        display: flex;
        justify-content: center;
    }

    .fc-daygrid-event-dot {
        border-width: 5px;

    }

    .fc-daygrid-event .fc-event-title {
        display: none;
    }

    .card {
        border-radius: 10px;
    }

    .card-body {
        padding: 16px;
    }

    /* EVENT BOX (TEXT MODE) */
    .fc-daygrid-event {
        margin: 2px 6px;
    }

    .fc-contract-event {
        font-size: 11px;
        padding: 4px 6px;
        border-radius: 6px;
        line-height: 1.3;
        white-space: normal;
        word-break: break-word;
        cursor: default;
    }

    .fc-contract-probation {
        background: #fff3cd;
        color: #664d03;
    }

    .fc-contract-promotion {
        background: #e7f1ff;
        color: #084298;
    }

    .fc-contract-evaluation {
        background: #f1f3f5;
        color: #343a40;
    }

    .fc-daygrid-event:hover {
        background: none;
    }

    /* ===== CONTRACT DOT ===== */
    .fc-contract-dot {
        width: 20px;
        height: 20px;
        background: #ff0000;
        border-radius: 50%;
        color: white;
        font-size: 11px;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 4px auto 0;
        cursor: pointer;
        box-shadow: 0 2px 4px rgba(0, 0, 0, .2);
    }

    .fc-contract-dot:hover {
        transform: scale(1.1);
    }
</style>

<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/schedule.js') }}?v={{ time() }}"></script>
<script>window.appBaseUrl = "{{ url('') }}".replace(/^http:\/\//i, window.location.protocol + '//');</script>
<script src="{{ asset('js/agenda.js') }}?v={{ time() }}"></script>