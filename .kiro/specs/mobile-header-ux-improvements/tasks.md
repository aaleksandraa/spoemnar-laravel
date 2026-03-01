# Implementation Plan: Mobile Header UX Improvements

## Overview

This implementation plan converts the mobile header UX improvements design into actionable coding tasks. The feature enhances mobile usability by: (1) relocating the theme toggle into the hamburger menu on mobile viewports, (2) implementing intelligent Login button visibility based on viewport width (<360px) and locale text length (de, it), and (3) modernizing the language selector with a 3-column grid layout. All changes maintain backward compatibility with desktop views and existing functionality.

The implementation uses PHP for Blade template logic, Tailwind CSS for responsive styling, and Alpine.js for state management. Each task builds incrementally, with property-based tests validating correctness properties from the design document.

## Tasks

- [ ] 1. Configure Tailwind custom breakpoint for extra-small viewports
  - Add `xs: '360px'` breakpoint to `tailwind.config.js`
  - Verify breakpoint works with `xs:` prefix classes
  - _Requirements: 8.3, 9.1_

- [ ] 2. Implement responsive Login button visibility logic
  - [ ] 2.1 Add locale-based visibility classes to mobile Login button
    - Update Login button in `#headerGuestMobileActions` section
    - Add conditional class: `{{ in_array(app()->getLocale(), ['de', 'it']) ? 'xs:hidden' : '' }}`
    - Ensure button has `hidden xs:inline-flex` base classes
    - _Requirements: 2.1, 2.3, 2.4_
  
  - [ ]* 2.2 Write property test for Login button visibility
    - **Property 4: Login Button Hidden on Small Viewports**
    - **Property 5: Login Button Hidden for Long Text Locales**
    - **Property 6: Both Buttons Visible for Standard Locales at Normal Width**
    - **Validates: Requirements 2.1, 2.3, 2.4**

- [ ] 3. Verify Registration button persistence
  - [ ] 3.1 Confirm Registration button has no responsive hiding classes
    - Check Registration button in `#headerGuestMobileActions`
    - Ensure it has `inline-flex` without `hidden` or conditional visibility
    - Verify accent gradient styling is present
    - _Requirements: 3.1, 3.2, 3.4_
  
  - [ ]* 3.2 Write property test for Registration button visibility
    - **Property 7: Registration Button Always Visible**
    - **Property 9: Registration Button Maintains Accent Styling**
    - **Validates: Requirements 3.1, 3.2, 3.4**

- [ ] 4. Checkpoint - Verify button visibility logic
  - Test mobile menu at different viewport widths (320px, 360px, 400px)
  - Test with different locales (sr, de, en, it)
  - Ensure all tests pass, ask the user if questions arise

- [ ] 5. Verify theme toggle is already in mobile menu
  - [ ] 5.1 Confirm theme toggle section structure
    - Verify theme toggle is first section in mobile menu
    - Check it has proper border-bottom separator
    - Ensure icons (sun/moon) display correctly based on theme
    - Verify `onclick="toggleDarkMode()"` handler is present
    - _Requirements: 1.1, 1.2, 1.4_
  
  - [ ]* 5.2 Write property tests for theme toggle location
    - **Property 1: Theme Toggle Location by Viewport**
    - **Property 2: Theme Toggle First in Mobile Menu**
    - **Property 3: Theme Toggle Executes Immediately**
    - **Validates: Requirements 1.1, 1.2, 1.3, 1.4, 1.5**

- [ ] 6. Verify mobile menu section order and styling
  - [ ] 6.1 Confirm section order matches design
    - Verify order: Theme Toggle → Navigation → Account Actions → Language Grid
    - Check all sections have border-top dividers (except first)
    - Verify section labels have uppercase styling and opacity-60
    - _Requirements: 4.1, 4.2, 4.3, 4.4_
  
  - [ ]* 6.2 Write property tests for mobile menu structure
    - **Property 10: Mobile Menu Section Order**
    - **Property 11: Mobile Menu Sections Have Consistent Styling**
    - **Property 12: Mobile Menu Hidden When Closed**
    - **Validates: Requirements 4.1, 4.2, 4.3, 4.4, 4.5**

- [ ] 7. Verify language grid layout is already modernized
  - [ ] 7.1 Confirm language grid structure
    - Check grid uses `grid-cols-3` layout
    - Verify all 6 locales are displayed
    - Confirm each item shows code (uppercase) and label
    - Check current locale has accent styling
    - Verify hover effects on non-current locales
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_
  
  - [ ]* 7.2 Write property tests for language grid
    - **Property 13: Language Grid Has Six Items in Three Columns**
    - **Property 14: Language Items Show Code and Name**
    - **Property 15: Current Locale Highlighted**
    - **Property 16: Language Items Have Consistent Card Styling**
    - **Property 17: Language Links Navigate to Localized URLs**
    - **Validates: Requirements 5.1, 5.2, 5.3, 5.4, 5.5**

- [ ] 8. Verify theme toggle functionality
  - [ ] 8.1 Confirm toggleDarkMode() function exists
    - Check function in existing JavaScript or layout file
    - Verify it toggles 'dark' class on document.documentElement
    - Ensure it persists to localStorage with key 'darkMode'
    - _Requirements: 6.1, 6.2, 6.3_
  
  - [ ]* 8.2 Write property tests for theme toggle behavior
    - **Property 18: Theme Toggle Round Trip**
    - **Property 19: Theme Restored from Storage**
    - **Property 20: Theme Falls Back to System Preference**
    - **Property 21: Theme Icon Matches Current Theme**
    - **Validates: Requirements 6.1, 6.2, 6.3, 6.4, 6.5**

- [ ] 9. Verify mobile menu interaction behavior
  - [ ] 9.1 Confirm Alpine.js state management
    - Check `x-data="{ mobileMenuOpen: false }"` on header element
    - Verify hamburger button has `@click="mobileMenuOpen = !mobileMenuOpen"`
    - Ensure mobile menu has `x-show="mobileMenuOpen"` and `x-transition`
    - Test click-outside behavior if implemented
    - _Requirements: 7.1, 7.2, 7.3, 7.5_
  
  - [ ]* 9.2 Write property tests for menu interaction
    - **Property 22: Hamburger Button Toggles Menu State**
    - **Property 23: Mobile Menu Has Transition Classes**
    - **Property 25: Alpine State Syncs with Menu Visibility**
    - **Validates: Requirements 7.1, 7.2, 7.3, 7.5**

- [ ] 10. Verify responsive breakpoint behavior
  - [ ] 10.1 Test desktop vs mobile layout switching
    - Verify desktop layout shows at ≥768px (md: breakpoint)
    - Verify mobile layout shows at <768px
    - Check hamburger button visibility toggles correctly
    - Ensure desktop theme toggle is hidden on mobile
    - _Requirements: 8.1, 8.2_
  
  - [ ]* 10.2 Write property tests for responsive breakpoints
    - **Property 26: Desktop Layout at Desktop Breakpoint**
    - **Property 27: Mobile Layout at Mobile Breakpoint**
    - **Property 28: Extra Small Breakpoint Classes Applied**
    - **Validates: Requirements 8.1, 8.2, 8.3**

- [ ] 11. Verify accessibility attributes
  - [ ] 11.1 Check ARIA labels and attributes
    - Verify theme toggle has `aria-label="{{ __('ui.theme.toggle_dark_mode') }}"`
    - Check hamburger button has `aria-label="Open menu"`
    - Ensure all interactive elements have proper labels
    - Verify SVG icons have `aria-hidden="true"`
    - _Requirements: 10.4, 10.5_
  
  - [ ]* 11.2 Write property tests for accessibility
    - **Property 31: Theme Toggle Has Accessibility Attributes**
    - **Property 32: Hamburger Button Has Accessibility Label**
    - **Validates: Requirements 10.4, 10.5**

- [ ] 12. Verify hover effects and visual feedback
  - [ ] 12.1 Confirm hover classes on interactive elements
    - Check theme toggle has `hover:bg-muted` or `hover:bg-white/10`
    - Verify language items have hover effects
    - Ensure buttons have `hover:` classes
    - Test transitions are smooth
    - _Requirements: 10.1, 10.2, 10.3_
  
  - [ ]* 12.2 Write property test for hover effects
    - **Property 30: Interactive Elements Have Hover Effects**
    - **Validates: Requirements 10.1, 10.2, 10.3**

- [ ] 13. Create comprehensive integration test suite
  - [ ]* 13.1 Write integration test for full mobile menu flow
    - Test opening mobile menu via hamburger button
    - Verify theme toggle works within menu
    - Test language selection and navigation
    - Verify Login button visibility based on locale and viewport
    - Test menu closing behavior
    - _Requirements: All_
  
  - [ ]* 13.2 Write integration test for responsive transitions
    - Test desktop to mobile viewport transition
    - Verify theme toggle moves from desktop to mobile menu
    - Test button visibility changes on resize
    - Verify Alpine.js state consistency
    - _Requirements: 8.1, 8.2, 8.4_

- [ ] 14. Final checkpoint - Complete feature validation
  - Run all property-based tests
  - Test on real devices at various viewport sizes
  - Verify all 6 locales work correctly
  - Test both light and dark themes
  - Ensure backward compatibility with desktop views
  - Ensure all tests pass, ask the user if questions arise

## Notes

- Tasks marked with `*` are optional property-based tests and can be skipped for faster MVP
- Most implementation is already complete in the existing header component
- Focus is on verification and adding the locale-based Login button visibility logic
- Property tests validate the 32 correctness properties from the design document
- Each property test references specific requirements for traceability
- Tailwind custom breakpoint configuration is the only infrastructure change needed
- All changes maintain backward compatibility with existing functionality
