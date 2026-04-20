"use strict";
var __assign = (this && this.__assign) || function () {
    __assign = Object.assign || function(t) {
        for (var s, i = 1, n = arguments.length; i < n; i++) {
            s = arguments[i];
            for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p))
                t[p] = s[p];
        }
        return t;
    };
    return __assign.apply(this, arguments);
};
var __spreadArray = (this && this.__spreadArray) || function (to, from, pack) {
    if (pack || arguments.length === 2) for (var i = 0, l = from.length, ar; i < l; i++) {
        if (ar || !(i in from)) {
            if (!ar) ar = Array.prototype.slice.call(from, 0, i);
            ar[i] = from[i];
        }
    }
    return to.concat(ar || Array.prototype.slice.call(from));
};
Object.defineProperty(exports, "__esModule", { value: true });
var props = defineProps();
debugger; /* PartiallyEnd: #3632/scriptSetup.vue */
var __VLS_ctx = {};
var __VLS_elements;
var __VLS_components;
var __VLS_directives;
__VLS_asFunctionalElement(__VLS_elements.div, __VLS_elements.div)(__assign({ class: "promotion__item" }));
__VLS_asFunctionalElement(__VLS_elements.img)({
    src: (props.image),
    alt: (props.title),
    loading: "lazy",
});
__VLS_asFunctionalElement(__VLS_elements.div, __VLS_elements.div)(__assign({ class: "content" }));
__VLS_asFunctionalElement(__VLS_elements.div, __VLS_elements.div)(__assign({ class: "title" }));
__VLS_asFunctionalElement(__VLS_elements.div)({});
__VLS_asFunctionalDirective(__VLS_directives.vHtml)(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { value: (props.title) }), null, null);
// @ts-ignore
[vHtml,];
__VLS_asFunctionalElement(__VLS_elements.div)({});
__VLS_asFunctionalDirective(__VLS_directives.vHtml)(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { value: (props.description) }), null, null);
// @ts-ignore
[vHtml,];
__VLS_asFunctionalElement(__VLS_elements.div, __VLS_elements.div)(__assign({ class: "d-flex justify-space-between align-center ga-2" }));
if (props.primaryAction) {
    var __VLS_0 = {}.SharedActionButton;
    /** @type {[typeof __VLS_components.SharedActionButton, typeof __VLS_components.sharedActionButton, ]} */ ;
    // @ts-ignore
    SharedActionButton;
    // @ts-ignore
    var __VLS_1 = __VLS_asFunctionalComponent(__VLS_0, new __VLS_0({
        action: (props.primaryAction.action),
        title: (props.primaryAction.title),
        color: (props.primaryAction.color),
    }));
    var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([{
            action: (props.primaryAction.action),
            title: (props.primaryAction.title),
            color: (props.primaryAction.color),
        }], __VLS_functionalComponentArgsRest(__VLS_1), false));
}
if (props.secondaryAction) {
    var __VLS_5 = {}.SharedActionButton;
    /** @type {[typeof __VLS_components.SharedActionButton, typeof __VLS_components.sharedActionButton, ]} */ ;
    // @ts-ignore
    SharedActionButton;
    // @ts-ignore
    var __VLS_6 = __VLS_asFunctionalComponent(__VLS_5, new __VLS_5({
        action: (props.secondaryAction.action),
        title: (props.secondaryAction.title),
        color: (props.secondaryAction.color),
    }));
    var __VLS_7 = __VLS_6.apply(void 0, __spreadArray([{
            action: (props.secondaryAction.action),
            title: (props.secondaryAction.title),
            color: (props.secondaryAction.color),
        }], __VLS_functionalComponentArgsRest(__VLS_6), false));
}
/** @type {__VLS_StyleScopedClasses['promotion__item']} */ ;
/** @type {__VLS_StyleScopedClasses['content']} */ ;
/** @type {__VLS_StyleScopedClasses['title']} */ ;
/** @type {__VLS_StyleScopedClasses['d-flex']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-space-between']} */ ;
/** @type {__VLS_StyleScopedClasses['align-center']} */ ;
/** @type {__VLS_StyleScopedClasses['ga-2']} */ ;
var __VLS_dollars;
var __VLS_self = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({
    setup: function () { return ({}); },
    __typeProps: {},
});
exports.default = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({
    __typeProps: {},
});
; /* PartiallyEnd: #4569/main.vue */
