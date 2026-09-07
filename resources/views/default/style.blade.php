<style type="text/css">
    /* Page */
    @page {
        /* margin-bottom gives space for the footer */
        margin: 48px 48px 80px 48px;
    }

    /* Base */
    *,
    *::before,
    *::after {
        box-sizing: border-box;
    }

    html {
        font-size: 16px;
        line-height: 24px;
    }

    body {
        margin: 0px;
        color: #050038;
        background-color: #fff;
        font-size: 16px;
        font-feature-settings: normal;
        font-variation-settings: normal;
        line-height: 24px;
        text-align: left;
    }

    blockquote,
    dl,
    dd,
    h1,
    h2,
    h3,
    h4,
    h5,
    h6,
    hr,
    figure,
    p,
    pre {
        margin: 0px;
    }

    h1,
    h2,
    h3,
    h4,
    h5,
    h6 {
        font-size: inherit;
        font-weight: inherit;
    }

    strong,
    b {
        font-weight: 700;
    }

    small {
        font-size: 12.8px;
    }

    sub,
    sup {
        position: relative;
        font-size: 12px;
        line-height: 0px;
        vertical-align: baseline;
    }

    sub {
        bottom: -3px;
    }

    sup {
        top: -6px;
    }

    a {
        color: #1d4ed8;
        text-decoration: none;
    }

    table {
        border-color: inherit;
        border-collapse: collapse;
        text-indent: 0px;
    }

    img,
    svg,
    video,
    canvas,
    audio,
    iframe,
    embed,
    object {
        display: block;
        vertical-align: middle;
    }

    img {
        border-style: none;
    }

    img,
    video {
        max-width: 100%;
        height: auto;
    }

    /* Display */
    .block {
        display: block;
    }

    .inline-block {
        display: inline-block;
    }

    /* Positioning */
    .fixed {
        position: fixed;
    }

    .-top-12 {
        top: -48px;
    }

    .-left-12 {
        left: -48px;
    }

    .-right-12 {
        right: -48px;
    }

    .-bottom-12 {
        bottom: -48px;
    }

    .-bottom-14 {
        bottom: -56px;
    }

    .-bottom-16 {
        bottom: -64px;
    }

    .-bottom-16\.5 {
        bottom: -66px;
    }

    .-bottom-20 {
        bottom: -80px;
    }

    .top-0 {
        top: 0px;
    }

    .right-0 {
        right: 0px;
    }

    .bottom-0 {
        bottom: 0px;
    }

    .left-0 {
        left: 0px;
    }

    /* Sizing */
    .w-full {
        width: 100%;
    }

    .min-w-28 {
        min-width: 112px;
    }

    .w-28 {
        width: 112px;
    }

    .h-2 {
        height: 8px;
    }

    .h-3 {
        height: 12px;
    }

    .size-1 {
        width: 4px;
        height: 4px;
    }

    .size-2 {
        width: 8px;
        height: 8px;
    }

    .size-4 {
        width: 16px;
        height: 16px;
    }

    /* Margin */
    .m-12 {
        margin: 48px;
    }

    .mx-12 {
        margin-right: 48px;
        margin-left: 48px;
    }

    .my-12 {
        margin-top: 48px;
        margin-bottom: 48px;
    }

    .mt-1 {
        margin-top: 4px;
    }

    .mt-3 {
        margin-top: 12px;
    }

    .mt-5 {
        margin-top: 24px;
    }

    .mt-12 {
        margin-top: 48px;
    }

    .mb-1 {
        margin-bottom: 4px;
    }

    .mb-2 {
        margin-bottom: 8px;
    }

    .mb-3 {
        margin-bottom: 12px;
    }

    .mb-5 {
        margin-bottom: 24px;
    }

    .mb-6 {
        margin-bottom: 24px;
    }

    .mb-8 {
        margin-bottom: 32px;
    }

    .mb-12 {
        margin-bottom: 48px;
    }

    .-ml-12 {
        margin-left: -48px;
    }

    .-mr-12 {
        margin-right: -48px;
    }

    /* Padding */
    .p-0 {
        padding: 0px;
    }

    .pr-0,
    .px-0 {
        padding-right: 0px;
    }

    .pl-0,
    .px-0 {
        padding-left: 0px;
    }

    .py-0\.5,
    .pt-0\.5 {
        padding-top: 2px;
    }

    .py-0\.5,
    .pb-0\.5 {
        padding-bottom: 2px;
    }

    .p-1 {
        padding: 4px;
    }

    .py-1,
    .pt-1 {
        padding-top: 4px;
    }

    .pr-1 {
        padding-right: 4px;
    }

    .pb-1 {
        padding-bottom: 4px;
    }

    .pr-2,
    .p-2 {
        padding-right: 8px;
    }

    .pl-2,
    .p-2 {
        padding-left: 8px;
    }

    .py-2,
    .pt-2,
    .p-2 {
        padding-top: 8px;
    }

    .py-2,
    .pb-2,
    .p-2 {
        padding-bottom: 8px;
    }

    .pt-5,
    .p-5 {
        padding-top: 20px;
    }

    .pr-5,
    .p-5 {
        padding-right: 20px;
    }

    .pb-5,
    .p-5 {
        padding-bottom: 20px;
    }

    .pl-5,
    .p-5 {
        padding-left: 20px;
    }

    .pt-6,
    .py-6,
    .p-6 {
        padding-top: 24px;
    }

    .pr-6,
    .px-6,
    .p-6 {
        padding-right: 24px;
    }

    .pb-6,
    .py-6,
    .p-6 {
        padding-bottom: 24px;
    }

    .pl-6,
    .px-6,
    .p-6 {
        padding-left: 24px;
    }

    .px-12,
    .pr-12,
    .p-12 {
        padding-right: 48px;
    }

    .px-12,
    .pl-12,
    .p-12 {
        padding-left: 48px;
    }

    /* Typography */
    .font-normal {
        font-weight: normal;
    }

    .text-xs {
        font-size: 12px;
        line-height: 16px;
    }

    .text-sm {
        font-size: 14px;
        line-height: 20px;
    }

    .text-2xl {
        font-size: 24px;
        line-height: 32px;
    }

    .text-3xl {
        font-size: 30px;
        line-height: 36px;
    }

    .text-left {
        text-align: left;
    }

    .text-right {
        text-align: right;
    }

    .align-top {
        vertical-align: top;
    }

    .align-text-top {
        vertical-align: text-top;
    }

    .whitespace-nowrap {
        white-space: nowrap;
    }

    .whitespace-pre-line {
        white-space: pre-line;
    }

    /* Borders */
    .border-b {
        border-bottom: 1px solid #e5e7eb;
    }

    .rounded-full {
        border-radius: 9999px;
    }

    /* Colors */
    .text-gray-500 {
        color: #6b7280;
    }

    .bg-white {
        background-color: #fff;
    }

    .bg-zinc-50 {
        background-color: #fafafa;
    }

    .bg-zinc-100 {
        background-color: #f4f4f5;
    }

    /* Generated content */
    .dompdf-page:after {
        content: counter(page);
    }
</style>
