---
name: Kinetic Blade - Sala de Esgrima
colors:
  surface: '#f8f9ff'
  surface-dim: '#cbdbf5'
  surface-bright: '#f8f9ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#eff4ff'
  surface-container: '#e5eeff'
  surface-container-high: '#dce9ff'
  surface-container-highest: '#d3e4fe'
  on-surface: '#0b1c30'
  on-surface-variant: '#3e4a38'
  inverse-surface: '#213145'
  inverse-on-surface: '#eaf1ff'
  outline: '#6e7b67'
  outline-variant: '#bdcbb4'
  surface-tint: '#096e00'
  primary: '#096e00'
  on-primary: '#ffffff'
  primary-container: '#4cd137'
  on-primary-container: '#055500'
  inverse-primary: '#5de146'
  secondary: '#565e74'
  on-secondary: '#ffffff'
  secondary-container: '#dae2fd'
  on-secondary-container: '#5c647a'
  tertiary: '#006e2d'
  on-tertiary: '#ffffff'
  tertiary-container: '#51cf70'
  on-tertiary-container: '#005421'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#7aff60'
  primary-fixed-dim: '#5de146'
  on-primary-fixed: '#012200'
  on-primary-fixed-variant: '#055300'
  secondary-fixed: '#dae2fd'
  secondary-fixed-dim: '#bec6e0'
  on-secondary-fixed: '#131b2e'
  on-secondary-fixed-variant: '#3f465c'
  tertiary-fixed: '#7ffc97'
  tertiary-fixed-dim: '#62df7d'
  on-tertiary-fixed: '#002109'
  on-tertiary-fixed-variant: '#005320'
  background: '#f8f9ff'
  on-background: '#0b1c30'
  surface-variant: '#d3e4fe'
typography:
  display-lg:
    fontFamily: Syne
    fontSize: 56px
    fontWeight: '800'
    lineHeight: 60px
    letterSpacing: -0.04em
  display-lg-mobile:
    fontFamily: Syne
    fontSize: 36px
    fontWeight: '800'
    lineHeight: 40px
    letterSpacing: -0.03em
  headline-lg:
    fontFamily: Syne
    fontSize: 36px
    fontWeight: '700'
    lineHeight: 44px
    letterSpacing: -0.03em
  headline-lg-mobile:
    fontFamily: Syne
    fontSize: 28px
    fontWeight: '700'
    lineHeight: 34px
    letterSpacing: -0.02em
  headline-md:
    fontFamily: Syne
    fontSize: 24px
    fontWeight: '700'
    lineHeight: 32px
    letterSpacing: -0.02em
  headline-sm:
    fontFamily: Syne
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
    letterSpacing: -0.01em
  title-lg:
    fontFamily: Space Grotesk
    fontSize: 18px
    fontWeight: '700'
    lineHeight: 24px
    letterSpacing: -0.01em
  title-md:
    fontFamily: Space Grotesk
    fontSize: 16px
    fontWeight: '600'
    lineHeight: 22px
    letterSpacing: 0em
  body-lg:
    fontFamily: Space Grotesk
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 26px
    letterSpacing: 0em
  body-md:
    fontFamily: Space Grotesk
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 22px
    letterSpacing: 0.01em
  body-sm:
    fontFamily: Space Grotesk
    fontSize: 12px
    fontWeight: '400'
    lineHeight: 18px
    letterSpacing: 0.02em
  label-lg:
    fontFamily: Space Grotesk
    fontSize: 13px
    fontWeight: '700'
    lineHeight: 16px
    letterSpacing: 0.08em
  label-md:
    fontFamily: Space Grotesk
    fontSize: 11px
    fontWeight: '700'
    lineHeight: 14px
    letterSpacing: 0.1em
  label-sm:
    fontFamily: Space Grotesk
    fontSize: 10px
    fontWeight: '700'
    lineHeight: 12px
    letterSpacing: 0.12em
spacing:
  space-xxs: 0.125rem
  space-xs: 0.25rem
  space-sm: 0.5rem
  space-md: 1rem
  space-lg: 1.5rem
  space-xl: 2rem
  space-2xl: 3rem
  space-3xl: 4.5rem
  gutter-mobile: 1rem
  gutter-desktop: 1.5rem
  margin-mobile: 1rem
  margin-tablet: 2rem
  margin-desktop: 3rem
---

## Brand & Style

This design system expresses the precision, velocity, and technical discipline of contemporary sport fencing. Inspired by high-performance competition strips and engineered foil craftsmanship, the aesthetic combines razor-sharp geometric discipline with high-contrast tactical athletics.

### Identity Principles
- **Blade Precision**: Zero-radius hard edges, hairline boundary rules, and razor-sharp containment. No softened corners or ambient glows.
- **Electric Impulse**: Kinetic signals delivered through high-voltage competition green accents (`#4CD137`), symbolizing target strikes, active piste timers, and valid hits.
- **Tournament Clarity**: Pure white backgrounds paired with deep navy structural contrasts that establish instantaneous visual priority, evoking Olympic piste markings and electronic scoring tables.

### Design Movement
**Kinetic Structuralism**: A synthesis of athletic Swiss typography and high-contrast performance engineering. The interface avoids rounded consumer styling in favor of stark rectilinear containers, technical data ribbons, and strict structural demarcation.

## Colors

The palette is engineered for instantaneous readability under intense athletic conditions. It relies on stark tonal separation between clinical whites, slate structural tiers, and electric tournament greens.

### Palette Architecture
- **Primary (`#4CD137`)**: The kinetic signal. Reserved for primary calls to action, active touch indicators, success states, and live match counters.
- **Primary Deep (`#16A34A`)**: The grounded press/focus state for primary elements, ensuring continuous WCAG AAA compliance against white and light slate surfaces.
- **Secondary (`#0F172A`)**: Carbon Obsidian. Used for high-impact structural headers, rigid boundaries, and dominant action anchors.
- **Tertiary (`#1E293B` to `#334155`)**: Slate armor. Handles secondary typography, technical labels, and structural dividers.
- **Neutral Background (`#FFFFFF`)**: Pure tournament piste ground.
- **Surface Containers**:
  - `surface-container-low`: `#F8FAFC` (App bars, filter bands, sub-panels)
  - `surface-container`: `#F1F5F9` (Cards, table header blocks, inactive cells)
  - `surface-container-high`: `#E2E8F0` (Borders, structural grid lines, data table ticks)
- **Status Accents**:
  - `fault / invalid touch`: `#EF4444` (Off-target, penalty card red)
  - `warning / priority`: `#F59E0B` (Yellow card, priority indicator)
  - `info`: `#0284C7` (Bout metadata, weapon registration)

## Typography

The typographic hierarchy juxtaposes the sculptured, tension-filled cuts of **Syne** with the technical, monospaced-adjacent legibility of **Space Grotesk**.

- **Display & Headlines (`Syne`)**: Used for branding banners, tournament standings, scores, and major sectional divisions. Syne communicates angular velocity, blade geometry, and avant-garde confidence.
- **Technical & Body Copy (`Space Grotesk`)**: Applied to bout sheets, membership rosters, timing logs, and administrative interfaces. Its mechanical construction mirrors tournament clocks and scoring apparatus.
- **Labels & Micro-data**: Always rendered in uppercase with expanded letter-spacing (`0.08em` to `0.12em`) to mirror match referee telemetry and competition tags.

## Layout & Spacing

Layouts follow an uncompromising, hard-grid framework rooted in 8px modular cadence (with 4px sub-intervals for technical tables).

### Grid Infrastructure
- **Desktop (1024px+)**: 12-column rigid grid with 24px gutters and 48px outer piste margins. Max-width content boundary is locked at 1440px.
- **Tablet (640px - 1023px)**: 8-column layout with 20px gutters and 32px margins. Reflows split-screen bout statistics into stacked horizontal bands.
- **Mobile (Below 640px)**: 4-column compact layout with 16px gutters and 16px margins. Complex tabular fencing data converts into vertically segmented, bordered data cards.

### Layout Principles
- **Grid Lines Visible**: Emphasize structural alignment using hairline borders (`#E2E8F0`) between layout tracks instead of floating whitespace.
- **Density**: High data density in management and scoring zones; expansive negative space across club presentation and hero surfaces.

## Elevation & Depth

This system rejects ambient shadows and soft blur filters. Depth is created strictly through **Tonal Stacking**, **Hairline Borders**, and **Hard Structural Offsets**.

### Depth Layers
- **Ground (`#FFFFFF`)**: Base viewport, white tournament piste.
- **Level 1 (`#F8FAFC`)**: Structural panels, section headers, and secondary sidebars bounded by a `1px solid #E2E8F0` hairline edge.
- **Level 2 (`#F1F5F9`)**: Active cards, selected cells, and data chips.
- **Level 3 Overlay (`#FFFFFF` with `#0F172A` boundary)**: Modals, flyout panels, and bout score sheets. Framed by a prominent `2px solid #0F172A` outline.

### Hard Kinetic Shadow
When an element demands physical elevation (e.g., floating action buttons or active scoring callouts), use an unblurred 0-radius directional offset:
`box-shadow: 4px 4px 0px 0px #0F172A`. On active interaction, collapse the offset to `1px 1px 0px 0px #0F172A` for instantaneous mechanical feedback.

## Shapes

The shape vocabulary is strictly **Sharp (0px)** across all visual components.

- **Zero Curvature**: Every corner—buttons, chips, inputs, modal containers, and avatars—terminates at 90 degrees.
- **Blade Motifs**: Decorative accents leverage 45-degree chamfered clips (`clip-path: polygon(...)`) on badge edges or active weapon tags to echo blade cross-sections (foil, épée, sabre).
- **Hairlines**: Boundaries rely on crisp `1px` or `2px` weights without anti-aliasing artifacts.

## Components

### Buttons
- **Primary Kinetic Button**:
  - Background: `#4CD137`
  - Text: `#0F172A`, `Syne`, weight 700, uppercase, `label-lg`.
  - Border: `2px solid #0F172A`
  - Shadow: `4px 4px 0px #0F172A`
  - Hover: Background `#3EC42A`, translate `-1px, -1px`, shadow `5px 5px 0px #0F172A`.
  - Active: Translate `2px, 2px`, shadow `1px 1px 0px #0F172A`.
- **Secondary Structural Button**:
  - Background: `#0F172A`
  - Text: `#FFFFFF`, weight 700.
  - Border: `2px solid #0F172A`.
  - Hover: Background `#1E293B`, border-color `#1E293B`.
- **Ghost Action**:
  - Transparent background, `1px solid #E2E8F0`, text `#0F172A`.
  - Hover: Background `#F1F5F9`, border-color `#0F172A`.

### Cards & Match Blocks
- Container: Background `#FFFFFF`, border `1px solid #E2E8F0`.
- Top Accent Ribbon: Optional `3px` solid `#4CD137` stripe on active/scheduled events.
- Typography: Headers in `Syne` (`headline-sm`), bout data in `Space Grotesk` (`body-md`).
- Hover State: Border switches from `#E2E8F0` to `#0F172A`.

### Form Fields & Inputs
- Container: Background `#F8FAFC`, border `1px solid #CBD5E1`, 0px border-radius.
- Text: `#0F172A`, `Space Grotesk` 14px.
- Focus: Background `#FFFFFF`, border `2px solid #0F172A`, outline none.
- Label: Positioned above input, uppercase, tracking `0.08em`, `#475569`.

### Chips & Weapon Badges
- Dimensions: Height 24px, padding 0 8px, 0px radius.
- States:
  - Neutral: Background `#F1F5F9`, text `#334155`, border `1px solid #E2E8F0`.
  - Active/Selected: Background `#0F172A`, text `#4CD137`, border `1px solid #0F172A`.
  - Live Bout Chip: Background `#4CD137`, text `#0F172A`, font-weight 700.

### Checkboxes & Radios
- Size: 18x18px square box, 0px radius.
- Border: `2px solid #0F172A`, background `#FFFFFF`.
- Checked: Fill with `#4CD137` featuring an inner sharp black square (or checkmark).

### Piste Data Tables
- Header Row: Background `#F1F5F9`, border-bottom `2px solid #0F172A`, text uppercase `label-md`.
- Rows: Clean `#FFFFFF` background with alternating zebra striping using `#F8FAFC`.
- Cells: Separated by vertical hairlines (`#E2E8F0`), numbers aligned right with tabular figures.