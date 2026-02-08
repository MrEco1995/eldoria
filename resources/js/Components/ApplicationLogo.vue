<template>




<svg width="400" height="400" viewBox="0 0 400 400"
     xmlns="http://www.w3.org/2000/svg">

  <defs>
    <!-- Glow Filter -->
    <filter id="glow">
      <feGaussianBlur stdDeviation="4" result="blur"/>
      <feMerge>
        <feMergeNode in="blur"/>
        <feMergeNode in="SourceGraphic"/>
      </feMerge>
    </filter>
  </defs>

  <!-- Hintergrund -->
  <rect width="400" height="400" fill="white"/>

  <!-- Äußerer Kreis -->
  <circle cx="200" cy="200" r="160"
          fill="none"
          stroke="#222"
          stroke-width="6"/>

  <!-- Rotierender Sigillen-Kreis -->
  <g>
    <circle cx="200" cy="200" r="130"
            fill="none"
            stroke="#777"
            stroke-width="2"
            stroke-dasharray="8 10"/>
    <animateTransform
      attributeName="transform"
      type="rotate"
      from="0 200 200"
      to="360 200 200"
      dur="40s"
      repeatCount="indefinite"/>
  </g>

  <!-- Magisches Symbol -->
  <path d="M200 110
           L215 150
           L260 160
           L215 170
           L200 210
           L185 170
           L140 160
           L185 150 Z"
        fill="none"
        stroke="#6b5cff"
        stroke-width="3"
        filter="url(#glow)">
    <animate
      attributeName="stroke-opacity"
      values="0.4;1;0.4"
      dur="3s"
      repeatCount="indefinite"/>
    <animate
      attributeName="stroke-width"
      values="2.5;4;2.5"
      dur="3s"
      repeatCount="indefinite"/>
  </path>

  <!-- Initialien -->
  <text x="200" y="240"
        text-anchor="middle"
        font-size="96"
        font-family="serif"
        fill="#111"
        letter-spacing="8">
    P&amp;P
  </text>

</svg>

</template>