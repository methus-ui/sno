import { NextRequest, NextResponse } from "next/server";

const BACKEND_URL = process.env.NEXT_PUBLIC_API_URL || "https://new.snocart.com/api/v1";

export async function GET(request: NextRequest) {
  try {
    const searchParams = request.nextUrl.searchParams;

    // Get parameters from query string
    const name = searchParams.get("name") || "";
    const limit = searchParams.get("limit") || "20";
    const offset = searchParams.get("offset") || "1";
    const latitude = searchParams.get("latitude");
    const longitude = searchParams.get("longitude");
    const zoneId = searchParams.get("zone_id") || "[2]";

    // Build backend URL
    let backendUrl = `${BACKEND_URL}/stores/search?name=${encodeURIComponent(name)}&limit=${limit}&offset=${offset}`;

    if (latitude && longitude) {
      backendUrl += `&latitude=${latitude}&longitude=${longitude}`;
    }

    // Call backend API with proper headers (server-to-server, no CORS issues)
    const response = await fetch(backendUrl, {
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
        zoneId: zoneId,
      },
    });

    if (!response.ok) {
      return NextResponse.json(
        { error: "Failed to search stores" },
        { status: response.status }
      );
    }

    const data = await response.json();
    return NextResponse.json(data);
  } catch (error) {
    console.error("Store search API proxy error:", error);
    return NextResponse.json(
      { error: "Internal server error" },
      { status: 500 }
    );
  }
}
