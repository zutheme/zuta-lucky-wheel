// =======================================================
//  ARCSHAPE.JS - Visual & Trigger Receiver
// =======================================================

class Arcshape {
  constructor(x, y, r, w, h, start, stop, background, opt, lb = '', order, txtcol, strurl, txtleft, txttop, txtsize) {
    const options = {
      isStatic: true,
      render: { fillStyle: background }
    };

    this.angle = 0;
    this.x = x;
    this.y = y;
    this.r = r;
    this.w = w;
    this.h = h;
    this.win = false; // Win state
    this.start = start;
    this.stop = stop;
    this.background = background;
    this.order = order;
    this.txtcol = txtcol;
    this.strurl = strurl;
    this.txtleft = txtleft;
    this.txttop = txttop;
    this.txtsize = txtsize || 16;
    this.label = lb || "arc"; 

    this.body = Bodies.circle(this.x, this.y, this.r, options);
    this.body.label = this.label;

    Composite.add(world, [this.body]);
  }

  remove() {
    Composite.remove(world, [this.body]);
  }

  update() {
    this.angle = this.angle + speed;
    this.x = centerX + Math.cos(PI / 8 + this.start + this.angle) * (w_arc / 2) * Math.cos(PI / 8);
    this.y = centerY + Math.sin(PI / 8 + this.start + this.angle) * (w_arc / 2) * Math.cos(PI / 8);
    
    this.body.position.x = this.x;
    this.body.position.y = this.y;
  }

  // Called explicitly by sketch.js when this slice is determined as the winner
  triggerWin() {
      if (this.win) return; // Prevent double trigger
      
      console.log(`zutalw Debug: Arcshape triggerWin called for ${this.label}`);
      
      this.win = true;
      this.background = colwin; // Highlight color

      // Play Sound
      try {
        if (typeof winsound !== 'undefined' && typeof winsound.play === 'function') {
             if (winsound.isPlaying()) winsound.stop();
             else winsound.play();
        }
      } catch (e) {}

      // Set global gift
      _getgift = this.label;

      // --- SỬA LỖI Ở ĐÂY ---
      // Kiểm tra xem cầu nối window.zutalw_show_win có tồn tại không
      console.log("zutalw Debug: Checking for popup function...");

      if (typeof window.zutalw_show_win === 'function') {
           console.log("zutalw Debug: Found window.zutalw_show_win. Calling it now.");
           setTimeout(() => {
               window.zutalw_show_win(this.label);
           }, 100);
      } else if (typeof get_popup === 'function') {
           // Fallback nếu get_popup vô tình được khai báo global (ít khi xảy ra)
           console.log("zutalw Debug: Found direct get_popup.");
           setTimeout(() => {
               get_popup(this.label, this.strurl, _idcampain);
           }, 100);
      } else {
           console.error("zutalw Error: Neither 'window.zutalw_show_win' nor 'get_popup' functions are defined!");
           alert("You won: " + this.label + "\n(Error: Popup function missing)");
      }
  }

  show() {
    push();
    translate(centerX, centerY);
    rotate(this.angle);
    
    if (this.win) {
      stroke("#FFD700");
      strokeWeight(4);
      fill(this.background);
    } else {
      fill(this.background);
      strokeWeight(0.5);
      noStroke();
    }
    
    arc(0, 0, this.w, this.h, this.start, this.stop);
    pop();

    // Draw Label
    push();
    translate(centerX, centerY);
    rotate(this.start + this.angle);
    
    textSize(this.txtsize);
    textWrap(WORD);
    textAlign(CENTER);
    
    if (this.win) {
        fill("#FFFFFF");
        textStyle(BOLD);
    } else {
        fill(this.txtcol);
        textStyle(NORMAL);
    }

    text(this.label, this.txtleft, this.txttop, 180, 200);
    pop();
  }
}

window.Arcshape = Arcshape;