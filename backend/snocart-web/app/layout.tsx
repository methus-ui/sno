import type { Metadata } from "next";
import { Inter } from "next/font/google";
import "./globals.css";

const inter = Inter({
  subsets: ["latin"],
  display: "swap",
  variable: "--font-inter",
});

export const metadata: Metadata = {
  title: "Snocart - Quick Commerce",
  description: "Get everything delivered in minutes. Browse stores, order products, and track deliveries in real-time.",
  keywords: "grocery delivery, quick commerce, online shopping, food delivery",
  authors: [{ name: "Snocart" }],
  viewport: "width=device-width, initial-scale=1, maximum-scale=1",
  themeColor: "#D91656",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="en" className={inter.variable}>
      <head>
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="anonymous" />
      </head>
      <body className="antialiased">
        {children}
      </body>
    </html>
  );
}
