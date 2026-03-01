# Design System Documentation

## Overview

This document describes the design system foundation for the Spomenar application. The design system provides a consistent set of colors, spacing, typography, shadows, and animations that ensure visual coherence across all pages.

## CSS Custom Properties

All design tokens are defined as CSS custom properties in `resources/css/app.css` and are available throughout the application.

### Colors

#### Light Mode
```css
--color-background: oklch(96% 0.02 60);      /* Main background */
--color-foreground: oklch(35% 0.03 40);      /* Main text color */
--color-card: oklch(100% 0 0);               /* Card background */
--color-primary: oklch(35% 0.03 40);         /* Primary brand color */
--color-secondary: oklch(90% 0.02 60);       /* Secondary color */
--color-accent: oklch(70% 0.12 80);          /* Accent color */
--color-muted: oklch(90% 0.02 60);           /* Muted backgrounds */
--color-border: oklch(85% 0.02 60);          /* Border color */
--color-destructive: oklch(60% 0.2 25);      /* Error/danger color */
--color-success: oklch(65% 0.15 145);        /* Success color */
--color-warning: oklch(75% 0.15 85);         /* Warning color */
--color-info: oklch(65% 0.15 240);           /* Info color */
```

#### Dark Mode
Dark mode is automatically applied based on system preferences or can be toggled manually. All colors are redefined for optimal contrast and readability in dark mode.

### Spacing

Consistent spacing scale based on 4px increments:

```css
--spacing-xs: 0.25rem;    /* 4px */
--spacing-sm: 0.5rem;     /* 8px */
--spacing-md: 1rem;       /* 16px */
--spacing-lg: 1.5rem;     /* 24px */
--spacing-xl: 2rem;       /* 32px */
--spacing-2xl: 3rem;      /* 48px */
--spacing-3xl: 4rem;      /* 64px */
--spacing-4xl: 6rem;      /* 96px */
```

**Usage in Tailwind:**
```html
<div class="p-4">      <!-- padding: 1rem (16px) -->
<div class="mt-8">     <!-- margin-top: 2rem (32px) -->
<div class="gap-6">    <!-- gap: 1.5rem (24px) -->
```

### Border Radius

```css
--radius-sm: 0.25rem;     /* 4px */
--radius-md: 0.5rem;      /* 8px */
--radius-lg: 0.75rem;     /* 12px */
--radius-xl: 1rem;        /* 16px */
--radius-2xl: 1.5rem;     /* 24px */
--radius-full: 9999px;    /* Fully rounded */
```

**Usage:**
```html
<button class="rounded-md">    <!-- 8px radius -->
<div class="rounded-lg">       <!-- 12px radius -->
<img class="rounded-full">     <!-- Circular -->
```

### Shadows

```css
--shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
--shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
--shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
--shadow-xl: 0 20px 25px rgba(0, 0, 0, 0.15);
--shadow-2xl: 0 25px 50px rgba(0, 0, 0, 0.25);
--shadow-elegant: 0 10px 40px -10px rgba(0, 0, 0, 0.1), 0 2px 8px -2px rgba(0, 0, 0, 0.06);
--shadow-gold: 0 4px 20px -2px rgba(218, 165, 32, 0.3);
```

**Usage:**
```html
<div class="shadow-md">        <!-- Standard shadow -->
<div class="shadow-elegant">   <!-- Custom elegant shadow -->
<div class="shadow-gold">      <!-- Gold accent shadow -->
```

### Typography

#### Font Families
```css
--font-family-serif: 'Playfair Display', serif;
--font-family-sans: 'Inter', system-ui, sans-serif;
```

**Usage:**
```html
<h1 class="font-serif">        <!-- Playfair Display -->
<p class="font-sans">          <!-- Inter -->
```

#### Font Sizes
```css
--font-size-xs: 0.75rem;      /* 12px */
--font-size-sm: 0.875rem;     /* 14px */
--font-size-base: 1rem;       /* 16px */
--font-size-lg: 1.125rem;     /* 18px */
--font-size-xl: 1.25rem;      /* 20px */
--font-size-2xl: 1.5rem;      /* 24px */
--font-size-3xl: 1.875rem;    /* 30px */
--font-size-4xl: 2.25rem;     /* 36px */
--font-size-5xl: 3rem;        /* 48px */
```

**Usage:**
```html
<h1 class="text-5xl">          <!-- 48px -->
<p class="text-base">          <!-- 16px -->
<small class="text-sm">        <!-- 14px -->
```

### Transitions

```css
--transition-fast: 150ms;
--transition-base: 300ms;
--transition-slow: 600ms;
--transition-ease: cubic-bezier(0.4, 0, 0.2, 1);
```

**Usage:**
```html
<button class="transition-smooth">     <!-- All properties, 300ms -->
<div class="transition-fast">          <!-- All properties, 150ms -->
```

### Z-Index Scale

```css
--z-dropdown: 1000;
--z-sticky: 1020;
--z-fixed: 1030;
--z-modal-backdrop: 1040;
--z-modal: 1050;
--z-popover: 1060;
--z-tooltip: 1070;
```

## Dark Mode

### Automatic Detection
The application automatically detects the user's system preference for dark mode using `prefers-color-scheme: dark` media query.

### Manual Toggle
Users can manually toggle dark mode using the dark mode button in the header. The preference is saved to `localStorage`.

### Implementation
```javascript
// Toggle dark mode
window.toggleDarkMode();

// Check if dark mode is active
document.documentElement.classList.contains('dark');
```

### Styling for Dark Mode
```html
<!-- Light mode only -->
<div class="bg-white dark:bg-gray-900">

<!-- Different colors in dark mode -->
<p class="text-gray-900 dark:text-gray-100">

<!-- Hide in dark mode -->
<svg class="block dark:hidden">

<!-- Show only in dark mode -->
<svg class="hidden dark:block">
```

## Utility Classes

### Gradients
```html
<div class="bg-gradient-hero">      <!-- Hero section gradient -->
<div class="bg-gradient-accent">    <!-- Accent gradient -->
<div class="bg-gradient-primary">   <!-- Primary gradient -->
```

### Animations
```html
<div class="animate-fade-in">       <!-- Fade in animation -->
<div class="animate-fade-in-up">    <!-- Fade in from bottom -->
<div class="animate-slide-in-left"> <!-- Slide from left -->
<div class="animate-pulse-subtle">  <!-- Subtle pulse effect -->
```

### Hover Effects
```html
<div class="hover-lift">            <!-- Lift on hover -->
<div class="hover-scale">           <!-- Scale on hover -->
```

### Glass Morphism
```html
<div class="glass">                 <!-- Frosted glass effect -->
```

## Scroll Animations

Elements with the `data-animate` attribute will automatically fade in when they enter the viewport:

```html
<section data-animate>
    <!-- Content will fade in when scrolled into view -->
</section>
```

## Accessibility

### Focus Indicators
All interactive elements have visible focus indicators:
```css
*:focus-visible {
  outline: 2px solid var(--color-ring);
  outline-offset: 2px;
}
```

### Reduced Motion
The design system respects user preferences for reduced motion:
```css
@media (prefers-reduced-motion: reduce) {
  /* All animations are disabled */
}
```

### Color Contrast
All color combinations meet WCAG AA standards for contrast ratios.

## Component Examples

### Button
```html
<button class="px-4 py-2 bg-primary text-primary-foreground rounded-md hover:opacity-90 transition-smooth">
    Click me
</button>
```

### Card
```html
<div class="bg-card border border-border rounded-lg p-6 shadow-md hover-lift">
    <h3 class="text-xl font-serif font-semibold mb-2">Card Title</h3>
    <p class="text-muted-foreground">Card content goes here.</p>
</div>
```

### Input
```html
<input 
    type="text" 
    class="w-full px-4 py-2 border border-input rounded-md bg-background focus:ring-2 focus:ring-ring focus:border-transparent transition-smooth"
    placeholder="Enter text..."
>
```

### Modal
```html
<div class="fixed inset-0 z-modal-backdrop bg-black/50 backdrop-blur-sm">
    <div class="fixed inset-0 z-modal flex items-center justify-center p-4">
        <div class="bg-card rounded-lg shadow-xl max-w-md w-full p-6 animate-fade-in-up">
            <!-- Modal content -->
        </div>
    </div>
</div>
```

## Best Practices

1. **Use Design Tokens**: Always use the defined CSS custom properties instead of hardcoded values
2. **Consistent Spacing**: Use the spacing scale (4px increments) for all margins, padding, and gaps
3. **Semantic Colors**: Use semantic color names (primary, secondary, accent) instead of specific colors
4. **Responsive Design**: Use Tailwind's responsive prefixes (sm:, md:, lg:, xl:)
5. **Dark Mode**: Always test components in both light and dark modes
6. **Accessibility**: Ensure all interactive elements are keyboard accessible and have proper ARIA labels
7. **Performance**: Use GPU-accelerated properties (transform, opacity) for animations
8. **Animation Duration**: Keep animations between 300-600ms for optimal user experience

## Testing

### Browser Testing
- Chrome/Edge: Last 2 versions
- Firefox: Last 2 versions
- Safari: Last 2 versions

### Responsive Testing
- Mobile: 320px - 767px
- Tablet: 768px - 1023px
- Desktop: 1024px+

### Accessibility Testing
- Screen reader compatibility
- Keyboard navigation
- Color contrast (WCAG AA)
- Reduced motion support

## Resources

- [Tailwind CSS Documentation](https://tailwindcss.com/docs)
- [OKLCH Color Space](https://oklch.com/)
- [WCAG Guidelines](https://www.w3.org/WAI/WCAG21/quickref/)
