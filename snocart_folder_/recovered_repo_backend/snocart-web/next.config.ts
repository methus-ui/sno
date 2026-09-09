import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  /* config options here */

  // Optimize production builds
  poweredByHeader: false,
  compress: true,

  // Image optimization
  images: {
    remotePatterns: [
      {
        protocol: "https",
        hostname: "new.snocart.com",
        pathname: "/storage/**",
      },
    ],
  },
};

export default nextConfig;
