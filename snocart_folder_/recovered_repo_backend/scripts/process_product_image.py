#!/usr/bin/env python3
"""
Remove background from product image and place on white 1:1 square canvas.
Usage: python3 process_product_image.py --input path/to/image --output path/to/output
"""

import argparse
import sys
from PIL import Image
Image.MAX_IMAGE_PIXELS = None

MAX_DIM = 4096  # downscale huge images before processing

def process_image(input_path, output_path):
    # Downscale oversized images first
    img = Image.open(input_path)
    w, h = img.size
    if w > MAX_DIM or h > MAX_DIM:
        ratio = min(MAX_DIM / w, MAX_DIM / h)
        img = img.resize((int(w * ratio), int(h * ratio)), Image.LANCZOS)
        img.save(input_path)

    from withoutbg import WithoutBG

    remover = WithoutBG.opensource()
    fg = remover.remove_background(input_path)

    bbox = fg.getbbox()
    if bbox is None:
        fg.save(output_path, "PNG")
        return

    cropped = fg.crop(bbox)
    cw, ch = cropped.size

    max_dim = max(cw, ch)
    padding = int(max_dim * 0.10)
    canvas_size = max_dim + padding * 2

    canvas = Image.new("RGBA", (canvas_size, canvas_size), (255, 255, 255, 255))
    offset_x = (canvas_size - cw) // 2
    offset_y = (canvas_size - ch) // 2
    canvas.paste(cropped, (offset_x, offset_y), cropped)

    final = Image.new("RGB", (canvas_size, canvas_size), (255, 255, 255))
    final.paste(canvas, mask=canvas.split()[3])
    final.save(output_path, "PNG")


if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("--input", required=True)
    parser.add_argument("--output", required=True)
    args = parser.parse_args()

    try:
        process_image(args.input, args.output)
    except Exception as e:
        print(f"Error: {e}", file=sys.stderr)
        sys.exit(1)
