class Particle {
  constructor(x, y, r, opt, lb = '', url, color) {
    let options = {
      isStatic: true,
      render: {
        fillStyle: 'green',
        strokeStyle: 'blue',
        lineWidth: 1
      }
    };
    if (opt) options = opt;

    this.x = x;
    this.y = y;
    this.r = r;
    this.url = url;
    this.color = color || colbut;
    this.colortext = coltextbut;
    this.start = 0;
    this.begin = 0;
    this.click = false;

    this.body = Bodies.circle(x, y, r, options);
    this.body.label = lb || "particle";

    Composite.add(world, [this.body]);
  }

  remove() {
    Composite.remove(world, [this.body]);
  }

  clicked() {
    const d = dist(mouseX, mouseY, this.x, this.y);
    if (d < this.r) {
      this.color = colbutpress;
      this.click = true;
      this.begin = new Date();
      return true;
    }
    return false;
  }

  show() {
    const pos = this.body.position;
    const angle = this.body.angle;

    // decorative arrow/shape (kept from original)
    push();
    noStroke();
    fill(this.color);
    beginShape();
    vertex(250 + 25, 250);
    vertex(250 + 25, 250 - 20);
    vertex(250 + 25 + 20, 250);
    vertex(250 + 25, 250 + 20);
    endShape(CLOSE);
    pop();

    // main circle
    push();
    translate(pos.x, pos.y);
    rotate(angle);
    rectMode(CENTER);
    strokeWeight(0.3);
    fill(this.color);
    ellipse(0, 0, this.r);
    pop();

    // button text
    push();
    translate(this.x, this.y);
    rotate(0);
    rectMode(CENTER);
    textAlign(CENTER);
    fill(this.colortext);
    textSize(16);
    textWrap(WORD);
    text(textbut, 0, 45, 100, 100);
    pop();
  }
}

window.Particle = Particle;
