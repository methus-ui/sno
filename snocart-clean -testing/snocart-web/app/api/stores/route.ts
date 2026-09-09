import { NextRequest, NextResponse } from "next/server";

const BACKEND_URL = process.env.NEXT_PUBLIC_API_URL || "https://new.snocart.com/api/v1";

export async function GET(request: NextRequest) {
  try {
    const searchParams = request.nextUrl.searchParams;

    // Get parameters from query string
    const limit = searchParams.get("limit") || "20";
    const offset = searchParams.get("offset") || "1";
    const zoneId = searchParams.get("zone_id") || "[2]";
    const moduleId = searchParams.get("module_id") || "4";
    const filter = searchParams.get("filter") || "all";

    // Build backend URL with all parameters (backend checks both headers and query params)
    const backendUrl = `${BACKEND_URL}/stores/get-stores/${filter}?limit=${limit}&offset=${offset}&zone_id=${encodeURIComponent(zoneId)}&module_id=${moduleId}`;

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
        { error: "Failed to fetch stores" },
        { status: response.status }
      );
    }

    const data = await response.json();
    return NextResponse.json(data);
  } catch (error) {
    console.error("Store API proxy error:", error);
    return NextResponse.json(
      { error: "Internal server error" },
      { status: 500 }
    );
  }
}
