<template>
    <component :is="iconComponent" :size="size" :stroke-width="strokeWidth" :class="iconClass" />
</template>

<script>
import {
    Search, Heart, ShoppingCart, Menu, X, LayoutGrid, Info, Truck, Phone,
    Headphones, Folder, Tag, Star, ChevronDown, ChevronLeft, ChevronRight,
    Minus, Plus, Trash2, Image, ShoppingBag, Eye, Package, Award, Handshake,
    Info as InfoIcon, ArrowRight, ArrowLeft, Home, Layers, CreditCard, CircleCheck,
    Boxes, DollarSign, Lightbulb, Box, MapPin, Mail, Clock, Send, SlidersHorizontal,
    ShieldCheck, RotateCcw, Banknote, Weight, Globe, Factory, Gift, Puzzle,
} from '@lucide/vue';

const ICON_MAP = {
    search: Search,
    heart: Heart,
    'shopping-cart': ShoppingCart,
    menu: Menu,
    x: X,
    'layout-grid': LayoutGrid,
    info: Info,
    truck: Truck,
    phone: Phone,
    headphones: Headphones,
    folder: Folder,
    tag: Tag,
    star: Star,
    'chevron-down': ChevronDown,
    'chevron-left': ChevronLeft,
    'chevron-right': ChevronRight,
    'sliders-horizontal': SlidersHorizontal,
    minus: Minus,
    plus: Plus,
    trash: Trash2,
    image: Image,
    'shopping-bag': ShoppingBag,
    eye: Eye,
    package: Package,
    award: Award,
    handshake: Handshake,
    'info-circle': InfoIcon,
    'arrow-right': ArrowRight,
    'arrow-left': ArrowLeft,
    home: Home,
    layers: Layers,
    'credit-card': CreditCard,
    'circle-check': CircleCheck,
    boxes: Boxes,
    dollar: DollarSign,
    lightbulb: Lightbulb,
    box: Box,
    'map-pin': MapPin,
    mail: Mail,
    clock: Clock,
    telegram: Send,
    'shield-check': ShieldCheck,
    'rotate-ccw': RotateCcw,
    banknote: Banknote,
    weight: Weight,
    globe: Globe,
    factory: Factory,
    gift: Gift,
    puzzle: Puzzle,
};

export default {
    name: 'AppIcon',
    props: {
        name: { type: String, required: true },
        size: { type: [Number, String], default: 20 },
        strokeWidth: { type: [Number, String], default: 1.75 },
        iconClass: { type: String, default: '' },
    },
    computed: {
        iconComponent() {
            return ICON_MAP[this.name] || Package;
        },
    },
};
</script>
